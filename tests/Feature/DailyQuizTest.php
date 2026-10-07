<?php

namespace Tests\Feature;

use App\Models\User;
use App\PracticeQuestionGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DailyQuizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-06 12:00:00', 'Asia/Kolkata'));
        Http::preventStrayRequests();
        foreach (PracticeQuestionGenerator::COUNTS as $period => $count) {
            $this->storeSet($period, $count);
        }
    }

    public function test_each_period_has_its_own_questions_scores_and_idempotent_submission(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (PracticeQuestionGenerator::COUNTS as $period => $count) {
            $response = $this->get(route($period))->assertOk()->assertDontSee('Private explanation');
            $questions = $response->viewData('questions');
            $this->assertCount($count, $questions);
            $this->assertSame($questions, $this->get(route($period))->viewData('questions'));
            $answers = array_column($questions, 'c');
            $answers[0] = -1;
            $payload = ['date' => app(PracticeQuestionGenerator::class)->date($period), 'set_number' => 1, 'answers' => $answers, 'score' => 999];
            $this->post(route($period.'.submit'), $payload)->assertRedirect(route($period));
            $this->post(route($period.'.submit'), $payload)->assertRedirect(route($period));
            $this->assertDatabaseHas('daily_quiz_attempts', ['period' => $period, 'score' => $count - 1, 'total' => $count]);
            $this->get(route($period))->assertViewHas('questions', [])->assertSee('Private explanation');
        }
        $this->assertDatabaseCount('daily_quiz_attempts', 3);
        $this->app['session']->flush();
        $this->get(route('monthly'))->assertViewHas('questions', []);
        $this->get(route('progress'))->assertSee('Monthly quiz')->assertSee('199/200 marks');
        Http::assertNothingSent();
    }

    public function test_guest_can_submit_but_cannot_submit_unopened_or_invalid_sets(): void
    {
        $payload = ['date' => '2026-10-06', 'set_number' => 1, 'answers' => [0]];
        $this->post(route('daily.submit'), $payload)->assertSessionHasErrors('answers');
        $questions = $this->get(route('daily'))->viewData('questions');
        $this->post(route('daily.submit'), $payload)->assertSessionHasErrors('answers');
        $this->post(route('daily.submit'), [...$payload, 'answers' => array_fill(0, 20, 4)])->assertSessionHasErrors('answers.0');
        $this->post(route('daily.submit'), [...$payload, 'set_number' => 2])->assertSessionHasErrors('set_number');
        $this->post(route('daily.submit'), [...$payload, 'answers' => array_column($questions, 'c')])->assertRedirect(route('daily'));
        $this->assertDatabaseHas('daily_quiz_attempts', ['user_id' => null, 'score' => 20]);
        $this->actingAs(User::factory()->create(['status' => 'blocked']))->get(route('daily'))->assertForbidden();
        $this->post(route('daily.submit'), $payload)->assertForbidden();
    }

    public function test_period_boundaries_and_stale_submission_rejection(): void
    {
        foreach (['daily' => '2026-10-07 00:01:00', 'weekly' => '2026-10-12 00:01:00', 'monthly' => '2026-11-01 00:01:00'] as $period => $nextDate) {
            $this->travelTo(Carbon::parse('2026-10-06 12:00:00', 'Asia/Kolkata'));
            $response = $this->get(route($period));
            $payload = ['date' => $response->viewData('date'), 'set_number' => 1, 'answers' => array_column($response->viewData('questions'), 'c')];
            $this->travelTo(Carbon::parse($nextDate, 'Asia/Kolkata'));
            $this->post(route($period.'.submit'), $payload)->assertSessionHasErrors('date');
            $this->storeSet($period, PracticeQuestionGenerator::COUNTS[$period]);
            $this->get(route($period))->assertOk()->assertViewHas('setNumber', 1);
        }
    }

    public function test_missing_sets_show_preparation_message_without_api_calls(): void
    {
        DB::table('practice_sets')->delete();
        foreach (array_keys(PracticeQuestionGenerator::COUNTS) as $period) {
            $this->get(route($period))->assertOk()->assertViewHas('questions', [])->assertSee('Questions for this period are being prepared.');
        }
        Http::assertNothingSent();
    }

    public function test_exam_redirects_and_custom_404_work(): void
    {
        foreach (['ssc', 'upsc', 'banking', 'railways', 'railway'] as $exam) {
            $this->get(route('exam', $exam))->assertRedirect(route('upcoming', ['exam' => $exam]));
        }
        $this->get('/missing-page')->assertNotFound()->assertSee('A wrong turn. A fresh start.');
    }

    private function storeSet(string $period, int $count): void
    {
        DB::table('practice_sets')->insert(['period' => $period, 'starts_on' => app(PracticeQuestionGenerator::class)->date($period), 'questions' => json_encode(array_map(fn (int $index): array => ['q' => $period.' question '.$index, 'o' => ['First', 'Second', 'Third', 'Fourth'], 'c' => $index % 4, 'explanation' => 'Private explanation '.$index], range(1, $count))), 'created_at' => now(), 'updated_at' => now()]);
    }
}
