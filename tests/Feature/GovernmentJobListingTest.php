<?php

namespace Tests\Feature;

use App\Models\JobNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernmentJobListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_includes_published_official_notices_but_hides_pending_notices(): void
    {
        $published = JobNotification::factory()->create([
            'title' => 'Junior Engineer Recruitment Notice', 'organization' => 'Central Public Works Department',
            'slug' => 'junior-engineer-recruitment', 'notice_type' => 'job', 'state' => 'Delhi', 'status' => 'published',
            'official_url' => 'https://cpwd.gov.in/recruitment/notice', 'source_checked_at' => now(), 'last_date' => today()->addDays(3),
        ]);
        JobNotification::factory()->create(['title' => 'Unreviewed Draft Notice', 'status' => 'pending']);

        $this->get(route('jobs.index'))->assertOk()
            ->assertSee('Junior Engineer Recruitment Notice')
            ->assertSee('Official source ↗')
            ->assertSee('Source checked on')
            ->assertSee(route('job-notices.show', ['year' => $published->created_at->format('Y'), 'slug' => $published->slug]), false)
            ->assertDontSee('Unreviewed Draft Notice');
    }

    public function test_listing_filters_by_notice_type_and_query_and_detail_has_confirmed_schema_fields(): void
    {
        $result = JobNotification::factory()->create([
            'title' => 'Assistant Examination Result', 'slug' => 'assistant-exam-result', 'notice_type' => 'result',
            'status' => 'published', 'official_url' => 'https://ssc.gov.in/result', 'last_date' => null,
        ]);
        JobNotification::factory()->create(['title' => 'Clerk Recruitment Notice', 'slug' => 'clerk-recruitment', 'notice_type' => 'job', 'status' => 'published', 'start_date' => today()]);

        $this->get(route('jobs.category', ['category' => 'results']))->assertOk()
            ->assertSee('Assistant Examination Result')->assertDontSee('Clerk Recruitment Notice');
        $this->get(route('jobs.index', ['q' => 'Assistant']))->assertOk()
            ->assertSee('Assistant Examination Result')->assertDontSee('Clerk Recruitment Notice');
        $this->get(route('job-notices.show', ['year' => $result->created_at->format('Y'), 'slug' => $result->slug]))
            ->assertOk()->assertDontSee('JobPosting')->assertSee('Assistant Examination Result')
            ->assertSee('Vacancies')->assertSee('Check official notice')->assertSee('https://ssc.gov.in/result');
        $job = JobNotification::query()->where('slug', 'clerk-recruitment')->firstOrFail();
        $this->get(route('job-notices.show', ['year' => $job->created_at->format('Y'), 'slug' => $job->slug]))
            ->assertOk()->assertSee('JobPosting');
    }

    public function test_public_listing_has_official_filter_pages_and_deadline_reminder_home_sections(): void
    {
        $notice = JobNotification::factory()->create([
            'title' => 'Rajasthan Assistant Recruitment', 'organization' => 'Rajasthan Public Service Commission',
            'slug' => 'rajasthan-assistant-recruitment', 'status' => 'published', 'state' => 'Rajasthan',
            'qualification_level' => 'graduate', 'source_checked_at' => now(), 'last_date' => today()->addDays(2),
        ]);

        $this->get(route('jobs.state', ['state' => 'rajasthan']))->assertOk()->assertSee($notice->title);
        $this->get(route('jobs.qualification', ['level' => 'graduate']))->assertOk()->assertSee($notice->title);
        $this->get(route('jobs.department', ['department' => 'rajasthan-public-service-commission']))->assertOk()->assertSee($notice->title);
        $this->get(route('home'))->assertOk()->assertSee("Today's New Jobs", false)->assertSee('Last date soon')->assertSee($notice->title);
    }

    public function test_homepage_lists_six_latest_published_official_jobs_and_job_detail_links_related_notices(): void
    {
        $jobs = collect(range(1, 7))->map(fn (int $number) => JobNotification::factory()->create([
            'title' => 'Official recruitment '.$number,
            'slug' => 'official-recruitment-'.$number,
            'state' => 'Delhi',
            'status' => 'published',
            'official_url' => 'https://ssc.gov.in/notice/'.$number,
            'source_checked_at' => now()->subMinutes(8 - $number),
        ]));
        JobNotification::factory()->create(['title' => 'Pending recruitment', 'status' => 'pending']);

        $homepage = $this->get(route('home'))->assertOk()->assertSee('Latest published jobs');
        foreach ($jobs->reverse()->take(6) as $job) {
            $homepage->assertSee($job->title);
        }
        $homepage->assertDontSee('Official recruitment 1')->assertDontSee('Pending recruitment');

        $firstJob = $jobs->last();
        $detail = $this->get(route('job-notices.show', ['year' => $firstJob->created_at->format('Y'), 'slug' => $firstJob->slug]))
            ->assertOk()->assertSee('Related government notices')->assertSee('"@type":"BreadcrumbList"', false);
        $detail->assertSee('Official recruitment 7')->assertSee('Official recruitment 6');
    }
}
