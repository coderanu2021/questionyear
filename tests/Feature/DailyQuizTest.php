<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DailyQuizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-04 12:00:00', 'Asia/Kolkata'));
        Quiz::factory()->create(['questions' => array_map(fn (int $index): array => [
            'q' => 'Practice question '.$index,
            'o' => ['First', 'Second', 'Third', 'Fourth'],
            'c' => $index % 4,
            'explanation' => 'Private explanation '.$index,
        ], range(1, 160))]);
    }

    public function test_guest_receives_50_questions_and_server_calculates_marks_once(): void
    {
        $response = $this->get(route('daily'))->assertOk()->assertDontSee('Private explanation');
        $questions = $response->viewData('questions');
        $this->assertCount(50, $questions);
        $this->assertSame($questions, $this->get(route('daily'))->viewData('questions'));
        $answers = array_column($questions, 'c');
        $answers[0] = -1;
        $payload = ['date' => '2026-10-04', 'set_number' => 1, 'answers' => $answers, 'score' => 999];
        $this->post(route('daily.submit'), $payload)->assertRedirect(route('daily'));
        $this->assertDatabaseHas('daily_quiz_attempts', ['user_id' => null, 'score' => 49, 'total' => 50]);
        $this->get(route('daily'))->assertSee('49 / 50')->assertSee('Private explanation')->assertViewHas('questions', []);
        $this->post(route('daily.submit'), $payload)->assertRedirect(route('daily'));
        $this->assertSame(1, DB::table('daily_quiz_attempts')->count());
        $this->post(route('daily.submit'), [...$payload, 'set_number' => 2])->assertSessionHasErrors('set_number');
    }

    public function test_logged_in_user_can_solve_three_distinct_sets_and_results_survive_a_new_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $seen = [];
        for ($set = 1; $set <= 3; $set++) {
            $response = $this->get(route('daily'))->assertOk();
            $questions = $response->viewData('questions');
            $this->assertSame($set, $response->viewData('setNumber'));
            $this->assertCount(50, $questions);
            $seen = [...$seen, ...array_column($questions, 'q')];
            $this->post(route('daily.submit'), ['date' => '2026-10-04', 'set_number' => $set, 'answers' => array_column($questions, 'c')])->assertRedirect(route('daily'));
        }
        $this->assertCount(150, array_unique($seen));
        $this->assertSame(150, (int) DB::table('daily_quiz_attempts')->where('user_id', $user->id)->sum('score'));
        $this->app['session']->flush();
        $this->get(route('daily'))->assertViewHas('questions', [])->assertSee('150 / 150');
        $this->get(route('progress'))->assertSee('Daily quiz results')->assertSee('50/50 marks');
        $this->post(route('daily.submit'), ['date' => '2026-10-04', 'set_number' => 4, 'answers' => [0]])->assertSessionHasErrors('set_number');
    }

    public function test_daily_quiz_resets_at_midnight_in_india_and_rejects_yesterdays_submission(): void
    {
        $questions = $this->get(route('daily'))->viewData('questions');
        $this->post(route('daily.submit'), ['date' => '2026-10-04', 'set_number' => 1, 'answers' => array_column($questions, 'c')])->assertRedirect();
        $this->travelTo(Carbon::parse('2026-10-05 00:01:00', 'Asia/Kolkata'));
        $next = $this->get(route('daily'))->assertOk();
        $this->assertCount(50, $next->viewData('questions'));
        $this->assertNotSame($questions, $next->viewData('questions'));
        $this->post(route('daily.submit'), ['date' => '2026-10-04', 'set_number' => 1, 'answers' => array_column($questions, 'c')])->assertSessionHasErrors('date');
    }

    public function test_invalid_answers_unopened_sets_and_blocked_accounts_are_rejected(): void
    {
        $payload = ['date' => '2026-10-04', 'set_number' => 1, 'answers' => [0]];
        $this->post(route('daily.submit'), $payload)->assertSessionHasErrors('answers');
        $questions = $this->get(route('daily'))->viewData('questions');
        $this->post(route('daily.submit'), $payload)->assertSessionHasErrors('answers');
        $this->post(route('daily.submit'), [...$payload, 'answers' => array_fill(0, 50, 4)])->assertSessionHasErrors('answers.0');
        $this->actingAs(User::factory()->create(['status' => 'blocked']))->get(route('daily'))->assertForbidden();
        $this->post(route('daily.submit'), [...$payload, 'answers' => array_column($questions, 'c')])->assertForbidden();
        $this->assertSame(0, DB::table('daily_quiz_attempts')->count());
    }

    public function test_daily_quiz_excludes_drafts_and_handles_empty_and_short_question_banks(): void
    {
        Chapter::query()->update(['status' => 'draft']);
        $this->get(route('daily'))->assertOk()->assertSee('There are no published questions yet.')->assertViewHas('questions', []);
        Quiz::factory()->create();
        $response = $this->get(route('daily'))->assertOk()->assertSee('This set currently contains 1 published questions.');
        $this->assertCount(1, $response->viewData('questions'));
    }

    public function test_exam_redirects_and_custom_404_work(): void
    {
        foreach (['ssc', 'upsc', 'banking', 'railways', 'railway'] as $exam) {
            $this->get(route('exam', $exam))->assertRedirect(route('upcoming', ['exam' => $exam]));
        }
        $this->get(route('upcoming'))->assertOk()->assertSee('Your next challenge is coming soon.');
        foreach (['/missing-page', '/subject/missing', '/learn/missing/0', '/exams/missing'] as $url) {
            $this->get($url)->assertNotFound()->assertSee('A wrong turn. A fresh start.')->assertSee(route('daily'));
        }
    }
}
