<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use App\PracticeQuestionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPracticeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_generation_creates_another_set_and_scheduler_keeps_both_sets(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        $batch = 0;
        Http::fake(function ($request) use (&$batch) {
            preg_match('/exactly (\d+)/', $request['contents'][0]['parts'][0]['text'], $matches);
            $prefix = 'Batch '.++$batch;
            $questions = array_map(fn (int $index): array => ['q' => $prefix.' Question '.$index, 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Explanation'], range(1, (int) $matches[1]));

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($questions)]]]]]]);
        });
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/practice')->assertOk()->assertSee('Pending generation')->assertSee('Generate Daily Quiz');
        $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertRedirect('/admin/practice')->assertSessionHasNoErrors();
        $snapshot = DB::table('practice_sets')->value('questions');
        $this->assertCount(20, json_decode($snapshot, true));
        $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertSessionHasNoErrors();
        $this->artisan('practice:generate daily')->assertSuccessful();
        $this->assertDatabaseCount('practice_sets', 2);
        $this->assertSame($snapshot, DB::table('practice_sets')->value('questions'));
        $this->get('/admin/practice')->assertOk()->assertSee('Generate another Daily Quiz')->assertSee('20 / 20')->assertSee('2 saved sets');
        Http::assertSentCount(2);
    }

    public function test_generation_requires_active_admin_and_valid_period(): void
    {
        Http::preventStrayRequests();
        $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertRedirect(route('login'));
        foreach ([['role' => 'student'], ['role' => 'admin', 'status' => 'blocked']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->get('/admin/practice')->assertForbidden();
            $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('admin.practice.generate'), ['period' => 'yearly'])->assertSessionHasErrors('period');
        Http::assertNothingSent();
    }

    public function test_api_failure_is_shown_without_publishing_a_quiz(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        Http::fake(['*' => Http::response([], 429)]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->followingRedirects()->post(route('admin.practice.generate'), ['period' => 'daily'])
            ->assertOk()->assertSee('Gemini generation failed (HTTP 429).');
        $this->assertDatabaseCount('practice_sets', 0);
    }

    public function test_failed_regeneration_preserves_the_existing_quiz_and_its_page(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        $questions = array_map(fn (int $index): array => ['q' => 'Existing question '.$index, 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Explanation'], range(1, 20));
        $snapshot = json_encode($questions);
        $id = DB::table('practice_sets')->insertGetId(['period' => 'daily', 'starts_on' => app(PracticeQuestionGenerator::class)->date('daily'), 'questions' => $snapshot, 'created_at' => now(), 'updated_at' => now()]);
        Http::fake(['*' => Http::response([], 429)]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson(route('admin.practice.generate'), ['period' => 'daily'])->assertUnprocessable();
        $this->assertDatabaseCount('practice_sets', 1);
        $this->assertDatabaseHas('practice_sets', ['id' => $id, 'questions' => $snapshot, 'set_number' => 1]);
        $this->get(route('daily.set', ['set' => $id]))->assertOk()->assertSee('Existing question 1');
    }

    public function test_duplicate_retry_limit_preserves_existing_sets_and_returns_a_clear_error(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        $question = ['q' => 'Repeated question', 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Explanation'];
        $snapshot = json_encode([$question]);
        $id = DB::table('practice_sets')->insertGetId(['period' => 'daily', 'starts_on' => app(PracticeQuestionGenerator::class)->date('daily'), 'questions' => $snapshot, 'created_at' => now(), 'updated_at' => now()]);
        Http::fake(function ($request) use ($question) {
            preg_match('/exactly (\d+)/', $request['contents'][0]['parts'][0]['text'], $matches);

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode(array_fill(0, (int) $matches[1], $question))]]]]]]);
        });
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson(route('admin.practice.generate'), ['period' => 'daily'])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Gemini could not provide enough unique questions after automatic retries. Try generation again later. Existing sets are preserved.');
        $this->assertDatabaseCount('practice_sets', 1);
        $this->assertDatabaseHas('practice_sets', ['id' => $id, 'questions' => $snapshot]);
        $this->get(route('daily.set', ['set' => $id]))->assertOk()->assertSee('Repeated question');
        Http::assertSentCount(4);
    }

    public function test_missing_api_key_shows_the_configuration_error(): void
    {
        config(['services.gemini.key' => null]);
        Subject::factory()->create();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->followingRedirects()->post(route('admin.practice.generate'), ['period' => 'daily'])
            ->assertOk()->assertSee('Set GEMINI_API_KEY before generating practice questions.');
        $this->assertDatabaseCount('practice_sets', 0);
        Http::assertNothingSent();
    }

    public function test_unavailable_model_shows_the_model_configuration_fix(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        Http::fake(['*' => Http::response([], 404)]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->followingRedirects()->post(route('admin.practice.generate'), ['period' => 'daily'])
            ->assertOk()->assertSee('Gemini model is unavailable (HTTP 404).')->assertSee('GEMINI_MODEL');
        $this->assertDatabaseCount('practice_sets', 0);
    }

    public function test_json_response_identifies_api_failures_without_exposing_the_key(): void
    {
        config(['services.gemini.key' => 'private-test-key']);
        Subject::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $failures = [
            [400, ['error' => ['details' => [['reason' => 'API_KEY_INVALID']], 'message' => 'private-test-key']], 'rejected the API key'],
            [401, [], 'rejected the API key'],
            [403, [], 'access denied'],
            [404, [], 'model or API URL was not found'],
            [429, [], 'quota or rate limit exceeded'],
            [503, [], 'temporarily unavailable'],
        ];
        $sequence = Http::fakeSequence();
        foreach ($failures as [$status, $body]) {
            $sequence->push($body, $status);
            if ($status === 404) {
                $sequence->push(['models' => []]);
            }
            if ($status === 503) {
                $sequence->push($body, $status)->push($body, $status);
            }
        }
        foreach ($failures as [$status, $body, $expected]) {
            $response = $this->postJson(route('admin.practice.generate'), ['period' => 'daily'])
                ->assertUnprocessable()->assertJsonPath('success', false);
            $this->assertStringContainsString($expected, $response->json('message'));
            $this->assertStringContainsString('HTTP '.$status, $response->json('message'));
            $this->assertStringNotContainsString('private-test-key', $response->getContent());
        }
        $this->assertDatabaseCount('practice_sets', 0);
    }

    public function test_unavailable_model_falls_back_to_a_listed_text_model_and_preserves_idempotency(): void
    {
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'models/old-model']);
        Subject::factory()->create();
        $questions = array_map(fn (int $index): array => ['q' => 'Fallback question '.$index, 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Explanation'], range(1, 20));
        Http::fake([
            '*/models/old-model:generateContent' => Http::response([], 404),
            '*/models?pageSize=1000' => Http::response(['models' => [
                ['name' => 'models/gemini-3.5-flash-lite', 'supportedGenerationMethods' => ['embedContent']],
            ], 'nextPageToken' => 'next-page']),
            '*/models?pageSize=1000&pageToken=next-page' => Http::response(['models' => [
                ['name' => 'models/gemini-3.5-flash-lite', 'supportedGenerationMethods' => ['generateContent']],
            ]]),
            '*/models/gemini-3.5-flash-lite:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($questions)]]]]]]),
        ]);
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson(route('admin.practice.generate'), ['period' => 'daily'])->assertOk()->assertJsonPath('success', true);
        $this->assertCount(20, json_decode(DB::table('practice_sets')->value('questions'), true));
        $this->artisan('practice:generate daily')->assertSuccessful();
        $this->assertDatabaseCount('practice_sets', 1);
        Http::assertSentCount(4);
    }

    public function test_temporary_service_failure_is_retried_and_publishes_only_one_quiz(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        $questions = array_map(fn (int $index): array => ['q' => 'Recovered question '.$index, 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Explanation'], range(1, 20));
        Http::fakeSequence()->push([], 503)->push([], 503)
            ->push(['candidates' => [['content' => ['parts' => [['text' => json_encode($questions)]]]]]]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson(route('admin.practice.generate'), ['period' => 'daily'])->assertOk()->assertJsonPath('success', true);
        $this->assertCount(20, json_decode(DB::table('practice_sets')->value('questions'), true));
        $this->artisan('practice:generate daily')->assertSuccessful();
        $this->assertDatabaseCount('practice_sets', 1);
        Http::assertSentCount(3);
    }
}
