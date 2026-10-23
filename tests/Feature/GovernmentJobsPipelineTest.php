<?php

namespace Tests\Feature;

use App\Jobs\SendGovernmentJobAlert;
use App\Jobs\SendGovernmentJobDigest as SendGovernmentJobDigestJob;
use App\Jobs\SendGovernmentJobTelegramAlert;
use App\Models\JobNotification;
use App\Models\JobPushSubscription;
use App\Models\JobSource;
use App\Models\RawNotice;
use App\Models\User;
use App\Services\JobAlerts\GovernmentJobAlertDispatcher;
use App\Services\JobAlerts\WebPushService;
use App\Services\JobParsers\HtmlJobNoticeParser;
use App\Services\JobParsers\PdfJobNoticeParser;
use App\Services\JobParsers\RssJobNoticeParser;
use App\Services\JobSources\GovernmentJobFetcher;
use App\Services\JobSources\HostRequestPacer;
use App\Services\JobSources\JobNoticeExtractor;
use App\Services\JobSources\JobNotificationDeduplicator;
use App\Services\JobSources\OfficialSourceHttpClient;
use App\Services\JobSources\OfficialSourceUrlPolicy;
use App\Services\JobSources\RobotsPolicy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Psr\Http\Client\ClientInterface;
use Tests\TestCase;

class GovernmentJobsPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_deduplication_hash_normalizes_notice_identity_fields(): void
    {
        $firstHash = JobNotification::deduplicationHash('CEN 07/2026', 'Railway Recruitment Boards', 'NTPC Undergraduate Posts');
        $sameNoticeHash = JobNotification::deduplicationHash('cen-07 / 2026', ' railway recruitment boards ', 'NTPC   Undergraduate Posts');
        $differentNoticeHash = JobNotification::deduplicationHash('CEN 08/2026', 'Railway Recruitment Boards', 'NTPC Undergraduate Posts');

        $this->assertSame($firstHash, $sameNoticeHash);
        $this->assertNotSame($firstHash, $differentNoticeHash);
        $notification = JobNotification::factory()->create([
            'advertisement_no' => 'CEN 07/2026', 'organization' => 'Railway Recruitment Boards', 'title' => 'NTPC Undergraduate Posts',
        ]);
        $this->assertSame($notification->id, app(JobNotificationDeduplicator::class)
            ->findDuplicate('cen-07 / 2026', ' railway recruitment boards ', 'NTPC   Undergraduate Posts')?->id);
    }

    public function test_ai_extraction_is_mocked_pending_and_leaves_unconfirmed_fields_null(): void
    {
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'test-model', 'services.job_notices.auto_publish' => false]);
        $source = JobSource::factory()->create(['url' => 'https://ssc.gov.in/', 'trusted' => true, 'terms_allows_automated_access' => true]);
        $rawNotice = RawNotice::factory()->create(['source_id' => $source->id, 'url' => 'https://ssc.gov.in/notice.pdf']);
        $extraction = [
            'org' => 'Staff Selection Commission', 'post' => 'Junior Assistant', 'summary' => 'Applications are invited for the advertised post.',
            'vacancies' => null, 'qualification_level' => null, 'qualification_details' => null, 'advertisement_no' => 'SSC/JA/2026',
            'fee' => null, 'age_limit' => null, 'start_date' => null, 'last_date' => null, 'pdf_url' => null,
            'application_url' => null, 'notice_type' => 'job', 'confidence' => 0.94,
        ];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($extraction, JSON_THROW_ON_ERROR)]]]]]], 200)]);

        $result = app(JobNoticeExtractor::class)->extract($rawNotice);

        $this->assertTrue($result['created']);
        $this->assertFalse($result['auto_published']);
        $this->assertSame('pending', $result['notification']->status);
        $this->assertNull($result['notification']->vacancies);
        $this->assertNull($result['notification']->last_date);
        $this->assertSame('https://ssc.gov.in/notice.pdf', $result['notification']->official_url);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'generativelanguage.googleapis.com') && $request->hasHeader('x-goog-api-key', 'test-key'));
    }

    public function test_high_confidence_trusted_source_can_auto_publish_only_when_enabled(): void
    {
        Bus::fake();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'test-model', 'services.job_notices.auto_publish' => true, 'services.job_notices.minimum_confidence' => 0.95]);
        $source = JobSource::factory()->create(['url' => 'https://upsc.gov.in/', 'trusted' => true, 'terms_allows_automated_access' => true]);
        $rawNotice = RawNotice::factory()->create(['source_id' => $source->id, 'url' => 'https://upsc.gov.in/notice.pdf']);
        $extraction = [
            'org' => 'Union Public Service Commission', 'post' => 'Assistant Director', 'summary' => 'The official notice invites applications.',
            'vacancies' => 4, 'qualification_level' => 'graduate', 'qualification_details' => null, 'advertisement_no' => 'AD/2026',
            'fee' => null, 'age_limit' => null, 'start_date' => null, 'last_date' => null, 'pdf_url' => null,
            'application_url' => null, 'notice_type' => 'job', 'confidence' => 0.98,
        ];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($extraction, JSON_THROW_ON_ERROR)]]]]]], 200)]);

        $result = app(JobNoticeExtractor::class)->extract($rawNotice);

        $this->assertTrue($result['auto_published']);
        $this->assertSame('published', $result['notification']->status);
        Bus::assertDispatched(SendGovernmentJobAlert::class);
        Bus::assertDispatched(SendGovernmentJobTelegramAlert::class);
    }

    public function test_expired_published_notices_close_without_guessing_null_dates(): void
    {
        $expired = JobNotification::factory()->create(['status' => 'published', 'last_date' => today()->subDay()]);
        $open = JobNotification::factory()->create(['status' => 'published', 'last_date' => today()]);
        $unknown = JobNotification::factory()->create(['status' => 'published', 'last_date' => null]);
        $pending = JobNotification::factory()->create(['status' => 'pending', 'last_date' => today()->subDay()]);

        $this->artisan('app:close-expired-job-notifications')->assertSuccessful();

        $this->assertSame('closed', $expired->fresh()->status);
        $this->assertSame('published', $open->fresh()->status);
        $this->assertSame('published', $unknown->fresh()->status);
        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_alert_and_digest_commands_dispatch_queue_jobs(): void
    {
        Bus::fake();
        $notification = JobNotification::factory()->create(['status' => 'published']);

        app(GovernmentJobAlertDispatcher::class)->dispatch($notification);
        $this->artisan('app:send-government-job-digest')->assertSuccessful();

        Bus::assertDispatched(SendGovernmentJobAlert::class);
        Bus::assertDispatched(SendGovernmentJobTelegramAlert::class);
        Bus::assertDispatched(SendGovernmentJobDigestJob::class);
    }

    public function test_daily_job_schedules_are_registered(): void
    {
        $commands = collect(Schedule::events())->pluck('command')->filter()->implode("\n");

        $this->assertStringContainsString('jobs:fetch', $commands);
        $this->assertStringContainsString('jobs:extract', $commands);
        $this->assertStringContainsString('close-expired-job-notifications', $commands);
        $this->assertStringContainsString('send-government-job-digest', $commands);
    }

    public function test_one_official_source_failure_does_not_stop_other_sources(): void
    {
        Cache::flush();
        JobSource::factory()->create(['name' => 'SSC', 'url' => 'https://ssc.gov.in/notices', 'parser' => 'html', 'terms_allows_automated_access' => true]);
        JobSource::factory()->create(['name' => 'UPSC', 'url' => 'https://upsc.gov.in/notices', 'parser' => 'html', 'terms_allows_automated_access' => true]);
        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')->atLeast()->once()->andReturnUsing(function (string $method, mixed $url): Response {
            $url = (string) $url;
            if (str_ends_with($url, '/robots.txt')) {
                return new Response(404);
            }
            if ($url === 'https://ssc.gov.in/notices') {
                return new Response(503);
            }
            if ($url === 'https://upsc.gov.in/notices') {
                return new Response(200, ['Content-Type' => 'text/html'], '<html><body><a href="/recruitment.html">Recruitment notification for assistant posts</a></body></html>');
            }
            if ($url === 'https://upsc.gov.in/recruitment.html') {
                return new Response(200, ['Content-Type' => 'text/html'], '<html><head><title>Recruitment notice</title></head><body>Official application terms and eligibility information.</body></html>');
            }

            return new Response(404);
        });
        $pacer = \Mockery::mock(HostRequestPacer::class);
        $pacer->shouldReceive('wait')->andReturnNull();
        $pacer->shouldReceive('setMinimumDelay')->andReturnNull();
        $urlPolicy = new OfficialSourceUrlPolicy;
        $httpClient = new OfficialSourceHttpClient($client, $pacer, $urlPolicy);
        $fetcher = new GovernmentJobFetcher($httpClient, $urlPolicy, new RobotsPolicy($httpClient, $urlPolicy, $pacer), new HtmlJobNoticeParser, new RssJobNoticeParser, new PdfJobNoticeParser);

        $summary = $fetcher->fetchActiveSources();

        $this->assertSame(['sources' => 2, 'fetched' => 1, 'skipped' => 0, 'failed' => 1], $summary);
        $this->assertDatabaseHas('raw_notices', ['source_id' => 2, 'url' => 'https://upsc.gov.in/recruitment.html']);
    }

    public function test_unreviewed_source_terms_pause_fetching_without_network_requests(): void
    {
        JobSource::factory()->create(['url' => 'https://ssc.gov.in/notices', 'terms_allows_automated_access' => false]);
        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldNotReceive('request');
        $pacer = \Mockery::mock(HostRequestPacer::class);
        $urlPolicy = new OfficialSourceUrlPolicy;
        $httpClient = new OfficialSourceHttpClient($client, $pacer, $urlPolicy);
        $fetcher = new GovernmentJobFetcher($httpClient, $urlPolicy, new RobotsPolicy($httpClient, $urlPolicy, $pacer), new HtmlJobNoticeParser, new RssJobNoticeParser, new PdfJobNoticeParser);

        $this->assertSame(['sources' => 1, 'fetched' => 0, 'skipped' => 1, 'failed' => 0], $fetcher->fetchActiveSources());
    }

    public function test_admin_must_link_same_site_policy_before_approving_source_access(): void
    {
        $source = JobSource::factory()->create(['url' => 'https://ssc.gov.in/', 'terms_allows_automated_access' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->get(route('admin.job-sources.index'))->assertOk()->assertSee('Fetch paused');
        $this->actingAs($admin)->put(route('admin.job-sources.update', $source), [
            'active' => '1',
            'terms_url' => 'https://ssc.gov.in/website-policy',
            'terms_allows_automated_access' => '1',
        ])->assertRedirect(route('admin.job-sources.index'));

        $this->assertDatabaseHas('job_sources', ['id' => $source->id, 'terms_url' => 'https://ssc.gov.in/website-policy', 'terms_allows_automated_access' => true]);
        $this->assertNotNull($source->fresh()->terms_reviewed_at);
    }

    public function test_push_subscription_accepts_supported_https_hosts_and_rejects_other_hosts(): void
    {
        $subscription = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token']];

        $this->postJson(route('jobs.push.store'), $subscription)->assertCreated()->assertJson(['subscribed' => true]);
        $this->assertDatabaseHas('job_push_subscriptions', ['endpoint_hash' => hash('sha256', $subscription['endpoint'])]);
        $this->postJson(route('jobs.push.store'), [...$subscription, 'endpoint' => 'https://attacker.invalid/fcm/send/abc'])->assertUnprocessable();
        $this->assertDatabaseCount('job_push_subscriptions', 1);
    }

    public function test_web_push_encrypts_notice_payload_and_uses_configured_vapid_keys(): void
    {
        Http::fake();
        $vapidKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        if (! $vapidKey) {
            $this->markTestSkipped('The local OpenSSL setup cannot generate P-256 keys.');
        }
        $vapidDetails = openssl_pkey_get_details($vapidKey);
        $subscriberKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        if (! $subscriberKey) {
            $this->markTestSkipped('The local OpenSSL setup cannot generate P-256 keys.');
        }
        $subscriberDetails = openssl_pkey_get_details($subscriberKey);
        $vapidPublicKey = $this->base64UrlEncode("\x04".$vapidDetails['ec']['x'].$vapidDetails['ec']['y']);
        $subscriberPublicKey = $this->base64UrlEncode("\x04".$subscriberDetails['ec']['x'].$subscriberDetails['ec']['y']);
        config(['services.job_alerts.vapid_public_key' => $vapidPublicKey, 'services.job_alerts.vapid_private_key' => $vapidDetails['key'], 'services.job_alerts.vapid_subject' => 'mailto:alerts@example.test']);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-subscription';
        JobPushSubscription::factory()->create([
            'endpoint' => $endpoint, 'endpoint_hash' => hash('sha256', $endpoint),
            'public_key' => $subscriberPublicKey, 'auth_token' => $this->base64UrlEncode(random_bytes(16)),
        ]);
        $notification = JobNotification::factory()->create(['status' => 'published', 'slug' => 'official-test-notice']);

        app(WebPushService::class)->send($notification);

        Http::assertSent(fn ($request): bool => $request->url() === $endpoint
            && $request->method() === 'POST'
            && str_starts_with($request->header('Content-Encoding')[0] ?? '', 'aes128gcm')
            && str_contains($request->header('Authorization')[0] ?? '', 'vapid t=')
            && strlen($request->body()) > 100);
        $this->assertNotNull(JobPushSubscription::query()->first()->last_notified_at);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
