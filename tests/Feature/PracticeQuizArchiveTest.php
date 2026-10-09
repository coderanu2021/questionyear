<?php

namespace Tests\Feature;

use App\Models\User;
use App\PracticeQuestionGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PracticeQuizArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00', 'Asia/Kolkata'));
        Http::preventStrayRequests();
    }

    private function storeSet(string $period, string $date, int $number, string $label): int
    {
        $questions = array_map(fn (int $index): array => ['q' => $label.' question '.$index, 'o' => ['A', 'B', 'C', 'D'], 'c' => 1, 'explanation' => 'Private answer explanation'], range(1, PracticeQuestionGenerator::COUNTS[$period]));

        return DB::table('practice_sets')->insertGetId(['period' => $period, 'starts_on' => $date, 'set_number' => $number, 'questions' => json_encode($questions), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_every_period_preserves_old_pages_and_separate_results_after_another_set_is_added(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (PracticeQuestionGenerator::COUNTS as $period => $count) {
            $date = app(PracticeQuestionGenerator::class)->date($period);
            $firstId = $this->storeSet($period, $date, 1, 'Original');
            $first = $this->get(route($period.'.set', ['set' => $firstId]))->assertOk()->assertSee('Original question 1')->assertDontSee('Private answer explanation');
            $payload = ['date' => $date, 'set_number' => 1, 'answers' => array_column($first->viewData('questions'), 'c')];
            $secondId = $this->storeSet($period, $date, 2, 'New');
            $this->get(route($period))->assertOk()->assertViewHas('setNumber', 2)->assertSee('New question 1')->assertSee(route($period.'.set', ['set' => $firstId]), false);
            $this->post(route($period.'.set.submit', ['set' => $firstId]), $payload)->assertRedirect(route($period.'.set', ['set' => $firstId]));
            $this->post(route($period.'.set.submit', ['set' => $firstId]), $payload)->assertRedirect();
            $this->get(route($period.'.set', ['set' => $firstId]))->assertViewHas('questions', [])->assertSee('Private answer explanation');
            $second = $this->get(route($period.'.set', ['set' => $secondId]))->assertOk()->assertViewHas('setNumber', 2)->assertDontSee('Private answer explanation');
            $this->assertCount($count, $second->viewData('questions'));
            $this->post(route($period.'.set.submit', ['set' => $secondId]), ['date' => $date, 'set_number' => 2, 'answers' => array_fill(0, $count, -1)])->assertRedirect();
            $this->assertDatabaseHas('daily_quiz_attempts', ['user_id' => $user->id, 'period' => $period, 'set_number' => 1, 'score' => $count]);
            $this->assertDatabaseHas('daily_quiz_attempts', ['user_id' => $user->id, 'period' => $period, 'set_number' => 2, 'score' => 0]);
            $this->assertSame($first->viewData('questions'), json_decode(DB::table('practice_sets')->where('id', $firstId)->value('questions'), true));
        }
        $this->assertDatabaseCount('daily_quiz_attempts', 6);
        Http::assertNothingSent();
    }

    public function test_previous_periods_can_be_opened_and_submitted_by_guests(): void
    {
        foreach (PracticeQuestionGenerator::COUNTS as $period => $count) {
            $id = $this->storeSet($period, '2026-09-01', 1, 'Archived');
            $response = $this->get(route($period.'.set', ['set' => $id]))->assertOk()->assertViewHas('date', '2026-09-01');
            $this->post(route($period.'.set.submit', ['set' => $id]), ['date' => '2026-09-01', 'set_number' => 1, 'answers' => array_column($response->viewData('questions'), 'c')])->assertRedirect();
            $this->get(route($period))->assertOk()->assertSee(route($period.'.set', ['set' => $id]), false);
        }
        $this->assertDatabaseCount('daily_quiz_attempts', 3);
    }

    public function test_unknown_wrong_period_and_unopened_sets_cannot_be_submitted(): void
    {
        $id = $this->storeSet('daily', '2026-10-09', 1, 'Daily');
        $this->get(route('weekly.set', ['set' => $id]))->assertNotFound();
        $this->get(route('daily.set', ['set' => 999999]))->assertNotFound();
        $payload = ['date' => '2026-10-09', 'set_number' => 1, 'answers' => array_fill(0, 20, 1)];
        $this->post(route('daily.set.submit', ['set' => $id]), $payload)->assertSessionHasErrors('answers');
        $this->get(route('daily.set', ['set' => $id]))->assertOk();
        $this->post(route('daily.set.submit', ['set' => $id]), [...$payload, 'set_number' => 2])->assertSessionHasErrors('set_number');
        $this->assertDatabaseCount('daily_quiz_attempts', 0);
    }

    public function test_archive_is_paginated_and_does_not_render_answer_keys(): void
    {
        for ($number = 1; $number <= 13; $number++) {
            $this->storeSet('daily', '2026-10-09', $number, 'Set '.$number);
        }
        $this->get(route('daily'))->assertOk()->assertSee('13 SAVED SETS')->assertSee('Older sets')->assertDontSee('Private answer explanation')->assertViewHas('archiveSets', fn ($sets): bool => $sets->count() === 12);
        $this->get(route('daily', ['page' => 2]))->assertOk()->assertViewHas('archiveSets', fn ($sets): bool => $sets->count() === 1);
    }

    public function test_quiz_set_urls_use_the_requested_format_and_redirect_old_links(): void
    {
        foreach (array_keys(PracticeQuestionGenerator::COUNTS) as $period) {
            $id = $this->storeSet($period, app(PracticeQuestionGenerator::class)->date($period), 1, 'Saved');
            $url = route($period.'.set', ['set' => $id]);
            $this->assertStringEndsWith('/'.$period.'-quiz/sets/quiz-set-'.$id, $url);
            $this->assertSame($url, route($period.'.set.submit', ['set' => $id]));
            $this->get('/'.$period.'-quiz/sets/'.$id)->assertStatus(301)->assertRedirect($url);
            $this->get($url)->assertOk()->assertSee('Saved question 1');
        }
    }

    public function test_migration_keeps_legacy_set_ids_and_question_snapshots(): void
    {
        $migration = require database_path('migrations/2026_10_09_072326_support_multiple_practice_sets_per_period.php');
        $migration->down();
        $id = DB::table('practice_sets')->insertGetId(['period' => 'daily', 'starts_on' => '2026-10-08', 'questions' => '[]', 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();
        $this->assertDatabaseHas('practice_sets', ['id' => $id, 'set_number' => 1, 'questions' => '[]']);
        $this->storeSet('daily', '2026-10-08', 2, 'Second');
        $this->assertDatabaseCount('practice_sets', 2);
    }
}
