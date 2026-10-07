<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PracticeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_reuses_fourteen_questions_and_saves_six_new_questions_once(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Quiz::factory()->create(['questions' => $this->questions(20, 'Saved')]);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($this->questions(6, 'New'))]]]]]])]);
        $this->artisan('practice:generate daily')->assertSuccessful();
        $questions = json_decode(DB::table('practice_sets')->value('questions'), true);
        $this->assertCount(20, $questions);
        $this->assertCount(14, array_filter($questions, fn (array $q): bool => str_starts_with($q['q'], 'Saved')));
        $this->assertSame(6, DB::table('practice_questions')->where('source', 'gemini')->count());
        $this->artisan('practice:generate daily')->assertSuccessful();
        $this->get(route('daily'))->assertOk()->assertViewHas('questions', $questions)->assertDontSee('Secret explanation');
        $this->get(route('daily'))->assertOk();
        Http::assertSentCount(1);
    }

    public function test_failure_and_invalid_responses_never_publish_incomplete_sets(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        Http::fake(['*' => Http::response([], 429)]);
        $this->artisan('practice:generate daily')->assertFailed();
        $this->assertDatabaseCount('practice_sets', 0);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '[{"q":"Invalid"}]']]]]]])]);
        $this->artisan('practice:generate daily')->assertFailed();
        $this->assertDatabaseCount('practice_sets', 0);
        $this->assertDatabaseCount('practice_questions', 0);
    }

    public function test_daily_mix_still_reuses_questions_when_there_are_ten_subjects(): void
    {
        config(['services.gemini.key' => 'test-key']);
        for ($index = 1; $index <= 10; $index++) {
            Quiz::factory()->create(['questions' => $this->questions(3, 'Saved subject '.$index)]);
        }
        $batch = 0;
        Http::fake(function () use (&$batch) {
            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($this->questions(1, 'New batch '.++$batch))]]]]]]);
        });
        $this->artisan('practice:generate daily')->assertSuccessful();
        $questions = json_decode(DB::table('practice_sets')->value('questions'), true);
        $this->assertCount(14, array_filter($questions, fn (array $q): bool => str_starts_with($q['q'], 'Saved')));
        Http::assertSentCount(6);
    }

    public function test_duplicate_api_questions_are_not_published(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        $questions = array_fill(0, 20, $this->questions(1, 'Duplicate')[0]);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($questions)]]]]]])]);
        $this->artisan('practice:generate daily')->assertFailed();
        $this->assertDatabaseCount('practice_sets', 0);
    }

    public function test_completed_questions_are_preserved_when_a_later_api_batch_fails(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->count(2)->create();
        $batch = 0;
        Http::fake(function ($request) use (&$batch) {
            $batch++;
            if ($batch === 2) {
                return Http::response([], 429);
            }
            preg_match('/exactly (\d+)/', $request['contents'][0]['parts'][0]['text'], $matches);

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($this->questions((int) $matches[1], $batch === 1 ? 'Completed' : 'Retry '.$batch))]]]]]]);
        });
        $this->artisan('practice:generate daily')->assertFailed();
        $this->assertDatabaseCount('practice_sets', 0);
        $this->assertDatabaseCount('practice_questions', 10);
        $this->artisan('practice:generate daily')->assertSuccessful();
        $questions = json_decode(DB::table('practice_sets')->value('questions'), true);
        $this->assertCount(20, $questions);
        $this->assertCount(7, array_filter($questions, fn (array $question): bool => str_starts_with($question['q'], 'Completed')));
    }

    public function test_weekly_and_monthly_generation_balances_subjects_and_exact_counts(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->count(2)->create();
        $batch = 0;
        Http::fake(function ($request) use (&$batch) {
            preg_match('/exactly (\d+)/', $request['contents'][0]['parts'][0]['text'], $matches);

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($this->questions((int) $matches[1], 'Batch '.++$batch))]]]]]]);
        });
        foreach (['weekly' => 50, 'monthly' => 200] as $period => $count) {
            $this->artisan('practice:generate '.$period)->assertSuccessful();
            $this->assertCount($count, json_decode(DB::table('practice_sets')->where('period', $period)->value('questions'), true));
        }
        $this->assertSame(2, DB::table('practice_questions')->distinct()->count('subject_id'));
    }

    private function questions(int $count, string $prefix): array
    {
        return array_map(fn (int $index): array => ['q' => $prefix.' question '.$index, 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Secret explanation'], range(1, $count));
    }
}
