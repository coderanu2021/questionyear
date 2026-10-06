<?php

namespace Tests\Feature;

use App\LearningTracker;
use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LearningFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Quiz $quiz;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-06 12:00:00', 'Asia/Kolkata'));
        $this->quiz = Quiz::factory()->create(['questions' => array_map(fn (int $index): array => ['q' => 'Unique question '.$index, 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Private explanation '.$index], range(1, 20))]);
        $this->learner = User::factory()->create();
        Http::preventStrayRequests();
    }

    public function test_wrong_answers_revision_and_subject_progress_are_tracked(): void
    {
        $this->actingAs($this->learner)->postJson('/quizzes/'.$this->quiz->id.'/answer', ['question' => 0, 'answer' => 1])->assertOk();
        $this->get(route('learning'))->assertOk()->assertSee('Unique question 1')->assertViewHas('dashboard', fn (array $dashboard): bool => $dashboard['wrongCount'] === 1 && $dashboard['todayCount'] === 1 && $dashboard['subjects']->first()->correct === 0);
        $id = $this->createPractice('revision');
        $this->get(route('learning.session', $id))->assertOk()->assertDontSee('Private explanation')->assertSee('Ready to begin?');
        $this->post(route('learning.start', $id))->assertRedirect();
        $this->post(route('learning.submit', $id), ['answers' => [0]])->assertRedirect();
        $this->get(route('learning.session', $id))->assertSee('Your result: 1 / 1')->assertSee('Private explanation');
        $this->assertDatabaseHas('learning_progress', ['user_id' => $this->learner->id, 'wrong' => false]);
        $this->assertDatabaseCount('learning_activity', 1);
    }

    public function test_bookmarks_are_private_and_can_be_practised_and_removed(): void
    {
        $source = 'quiz:'.$this->quiz->id.':0';
        $this->actingAs($this->learner)->postJson(route('learning.bookmark'), ['source' => $source, 'bookmarked' => true])->assertOk()->assertJsonPath('bookmarked', true);
        $this->get(route('learning', ['filter' => 'bookmarked']))->assertSee('Unique question 1')->assertDontSee('Private explanation');
        $id = $this->createPractice('bookmarks');
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('learning.session', $id))->assertNotFound();
        $this->get(route('learning', ['filter' => 'bookmarked']))->assertDontSee('Unique question 1');
        $this->actingAs($this->learner)->postJson(route('learning.bookmark'), ['source' => $source, 'bookmarked' => false])->assertOk();
        $this->assertDatabaseHas('learning_progress', ['user_id' => $this->learner->id, 'bookmarked' => false]);
    }

    public function test_mock_timer_survives_reload_and_late_answers_are_rejected(): void
    {
        $this->actingAs($this->learner);
        $id = $this->createPractice('mock');
        $questions = json_decode(DB::table('learning_sessions')->where('id', $id)->value('questions'), true);
        $answers = array_column($questions, 'c');
        $this->post(route('learning.submit', $id), ['answers' => $answers])->assertForbidden();
        $this->post(route('learning.start', $id))->assertRedirect();
        $started = DB::table('learning_runs')->value('started_at');
        $this->travel(2)->minutes();
        $this->post(route('learning.start', $id))->assertRedirect();
        $this->assertSame($started, DB::table('learning_runs')->value('started_at'));
        $this->get(route('learning.session', $id))->assertOk()->assertDontSee('Private explanation');
        $this->travel(9)->minutes();
        $this->post(route('learning.submit', $id), ['answers' => $answers])->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('learning_runs', ['session_id' => $id, 'score' => 0]);
        $this->post(route('learning.submit', $id), ['answers' => $answers])->assertRedirect();
        $this->assertDatabaseCount('learning_runs', 1);
    }

    public function test_friend_challenge_has_identical_questions_and_independent_results(): void
    {
        $this->actingAs($this->learner);
        $id = $this->createPractice('challenge');
        $questions = json_decode(DB::table('learning_sessions')->where('id', $id)->value('questions'), true);
        foreach ([$this->learner, User::factory()->create()] as $user) {
            $this->actingAs($user)->get(route('learning.session', $id))->assertOk()->assertSee('Copy challenge link');
            $this->post(route('learning.start', $id))->assertRedirect();
            $this->post(route('learning.submit', $id), ['answers' => array_column($questions, 'c')])->assertRedirect();
        }
        $this->get(route('learning.session', $id))->assertSee($this->learner->name)->assertViewHas('scores', fn ($scores): bool => $scores->count() === 2);
        $this->assertDatabaseCount('learning_runs', 2);
        $this->travel(8)->days();
        $this->get(route('learning.session', $id))->assertStatus(410);
    }

    public function test_reports_are_saved_once_and_only_admins_can_review_them(): void
    {
        $payload = ['source' => 'quiz:'.$this->quiz->id.':0', 'reason' => 'The correct answer looks wrong.'];
        $this->actingAs($this->learner)->postJson(route('learning.report'), $payload)->assertOk();
        $this->postJson(route('learning.report'), $payload)->assertOk();
        $this->assertDatabaseCount('question_reports', 1);
        $this->get(route('learning.reports'))->assertForbidden();
        $id = DB::table('question_reports')->value('id');
        $this->post(route('learning.report.update', $id), ['status' => 'resolved'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('learning.reports'))->assertOk()->assertSee($payload['reason']);
        $this->post(route('learning.report.update', $id), ['status' => 'resolved', 'admin_note' => 'Reviewed the source'])->assertRedirect();
        $this->assertDatabaseHas('question_reports', ['id' => $id, 'status' => 'resolved']);
    }

    public function test_hindi_translation_is_cached_and_hidden_until_answered(): void
    {
        config(['services.gemini.key' => 'test-key']);
        $payload = ['source' => 'quiz:'.$this->quiz->id.':0', 'language' => 'hi'];
        $this->actingAs($this->learner)->postJson(route('learning.explanation'), $payload)->assertForbidden();
        $this->postJson('/quizzes/'.$this->quiz->id.'/answer', ['question' => 0, 'answer' => 1])->assertOk();
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode(['explanation' => 'सही उत्तर का विवरण'])]]]]]])]);
        $this->postJson(route('learning.explanation'), $payload)->assertOk()->assertJsonPath('explanation', 'सही उत्तर का विवरण');
        $this->postJson(route('learning.explanation'), $payload)->assertOk();
        Http::assertSentCount(1);
        $this->postJson(route('learning.explanation'), [...$payload, 'language' => 'en'])->assertOk()->assertJsonPath('explanation', 'Private explanation 1');
        $other = User::factory()->create();
        $this->actingAs($other)->postJson(route('learning.explanation'), $payload)->assertForbidden();
    }

    public function test_daily_goal_streak_and_weekly_points_cannot_be_inflated_by_retries(): void
    {
        $this->actingAs($this->learner)->post(route('learning.preferences'), ['daily_target' => 5, 'language' => 'hi'])->assertRedirect();
        $tracker = app(LearningTracker::class);
        $questions = array_slice($this->quiz->questions, 0, 5);
        $tracker->record($this->learner, $questions, [0, 0, 0, 0, 0], $this->quiz->chapter->subject_id);
        $tracker->record($this->learner, $questions, [0, 0, 0, 0, 0], $this->quiz->chapter->subject_id);
        $this->assertDatabaseCount('learning_activity', 5);
        $this->get(route('learning'))->assertViewHas('dashboard', fn (array $data): bool => $data['streak'] === 1 && $data['todayCount'] === 5 && $data['language'] === 'hi');
        $this->travel(1)->days();
        $tracker->record($this->learner, $questions, [0, 0, 0, 0, 0], $this->quiz->chapter->subject_id);
        $this->get(route('learning'))->assertViewHas('dashboard', fn (array $data): bool => $data['streak'] === 2);
        $this->get(route('learning.leaderboard'))->assertViewHas('scores', fn ($scores): bool => $scores->first()->score === 10);
        $this->travel(2)->days();
        $this->get(route('learning'))->assertViewHas('dashboard', fn (array $data): bool => $data['streak'] === 0);
    }

    public function test_guests_blocked_users_and_draft_sources_cannot_use_learning_actions(): void
    {
        $payload = ['source' => 'quiz:'.$this->quiz->id.':0', 'bookmarked' => true];
        $this->postJson(route('learning.bookmark'), $payload)->assertUnauthorized();
        $this->actingAs(User::factory()->create(['status' => 'blocked']))->get(route('learning'))->assertForbidden();
        $this->postJson(route('learning.bookmark'), $payload)->assertForbidden();
        $this->quiz->chapter->update(['status' => 'draft']);
        $this->actingAs($this->learner)->postJson(route('learning.bookmark'), $payload)->assertNotFound();
        $this->postJson(route('learning.bookmark'), [...$payload, 'source' => 'quiz:99999:0'])->assertNotFound();
    }

    public function test_existing_attempt_history_is_imported_once_without_overwriting_new_revision(): void
    {
        $answers = array_fill(0, 20, 0);
        $answers[0] = 1;
        Attempt::factory()->create(['quiz_id' => $this->quiz->id, 'user_id' => $this->learner->id, 'answers' => $answers, 'score' => 19, 'total' => 20, 'created_at' => now()->subDay()]);
        app(LearningTracker::class)->record($this->learner, [$this->quiz->questions[0]], [0], $this->quiz->chapter->subject_id);
        $this->actingAs($this->learner)->get(route('learning'))->assertOk()->assertViewHas('dashboard', fn (array $data): bool => $data['wrongCount'] === 0 && $data['todayCount'] === 1 && $data['streak'] === 1);
        $this->assertDatabaseCount('learning_activity', 21);
        $this->get(route('learning'))->assertOk();
        $this->assertDatabaseCount('learning_activity', 21);
        $this->assertDatabaseHas('learning_preferences', ['user_id' => $this->learner->id, 'history_imported' => true]);
    }

    public function test_exam_leaderboard_filters_out_other_exam_groups_and_blocked_users(): void
    {
        $this->actingAs($this->learner)->post(route('learning.preferences'), ['daily_target' => 10, 'language' => 'en', 'exam' => 'ssc'])->assertRedirect();
        app(LearningTracker::class)->record($this->learner, [$this->quiz->questions[0]], [0], $this->quiz->chapter->subject_id);
        $other = User::factory()->create();
        app(LearningTracker::class)->record($other, [$this->quiz->questions[0]], [0], $this->quiz->chapter->subject_id);
        $this->get(route('learning.leaderboard', ['exam' => 'ssc']))->assertOk()->assertViewHas('scores', fn ($scores): bool => $scores->count() === 1 && $scores->first()->name === $this->learner->name);
        $this->learner->status = 'blocked';
        $this->learner->save();
        $this->get(route('learning.leaderboard', ['exam' => 'ssc']))->assertOk()->assertViewHas('scores', fn ($scores): bool => $scores->isEmpty());
    }

    public function test_translation_failure_does_not_store_invalid_content(): void
    {
        config(['services.gemini.key' => 'test-key']);
        $this->actingAs($this->learner)->postJson('/quizzes/'.$this->quiz->id.'/answer', ['question' => 0, 'answer' => 0])->assertOk();
        Http::fake(['*' => Http::response([], 429)]);
        $this->postJson(route('learning.explanation'), ['source' => 'quiz:'.$this->quiz->id.':0', 'language' => 'hi'])->assertStatus(503);
        $this->assertDatabaseHas('learning_questions', ['explanation_hi' => null]);
    }

    public function test_unpublished_questions_are_not_reused_in_new_mock_tests(): void
    {
        $this->actingAs($this->learner);
        $this->createPractice('mock');
        $this->quiz->chapter->update(['status' => 'draft']);
        $this->post(route('learning.create'), ['mode' => 'mock', 'count' => 10])->assertSessionHasErrors('mode');
        $this->assertDatabaseCount('learning_sessions', 1);
    }

    private function createPractice(string $mode): string
    {
        $response = $this->post(route('learning.create'), ['mode' => $mode, 'count' => 10])->assertRedirect();

        return basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }
}
