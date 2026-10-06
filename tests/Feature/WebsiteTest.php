<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurriculumSeeder::class);
    }

    public function test_public_pages_render_and_missing_chapters_return_404(): void
    {
        foreach (['/', '/login', '/register', '/subject/history', '/learn/history/0', '/quiz/history/0'] as $url) {
            $this->get($url)->assertOk()->assertSee('questionyear');
        }
        $this->get('/subject/missing')->assertNotFound();
        $this->get('/learn/history/999')->assertNotFound();
    }

    public function test_registration_hashes_password_and_prevents_admin_role_injection(): void
    {
        Notification::fake();
        $this->postJson('/account/register', ['name' => 'Learner', 'email' => 'learner@example.com', 'password' => 'StrongPassword123', 'role' => 'admin'])->assertCreated();
        $user = User::where('email', 'learner@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('StrongPassword123', $user->password));
        $this->assertSame('student', $user->role);
        $this->assertGuest();
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/admin')->assertRedirect(route('login'));
        $this->postJson('/account/logout')->assertOk();
        $this->assertGuest();
        $this->postJson('/account/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        $this->postJson('/account/login', ['email' => $user->email, 'password' => 'StrongPassword123'])->assertForbidden()->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
        $this->assertGuest();
        $user->markEmailAsVerified();
        $this->postJson('/account/login', ['email' => $user->email, 'password' => 'StrongPassword123'])->assertOk();
    }

    public function test_admin_login_returns_dashboard_and_progress_has_visible_admin_link(): void
    {
        $admin = User::factory()->unverified()->create(['role' => 'admin', 'password' => 'StrongPassword123']);
        $this->postJson('/account/login', ['email' => $admin->email, 'password' => 'StrongPassword123'])
            ->assertOk()->assertJsonPath('redirect', route('admin'));
        $this->get('/admin')->assertOk()->assertViewIs('admin.index');
        $this->get('/login')->assertRedirect(route('admin'));
        $this->get(route('admin.chapters.create'))->assertOk()->assertViewHas('chapterPage', ['id' => null, 'mode' => 'edit']);
        $chapter = Chapter::firstOrFail();
        $this->get(route('admin.chapters.edit', $chapter))->assertOk()->assertViewHas('chapterPage', ['id' => $chapter->id, 'mode' => 'edit']);
        $this->get(route('admin.chapters.show', $chapter))->assertOk()->assertViewHas('chapterPage', ['id' => $chapter->id, 'mode' => 'show']);
        $this->get('/admin/chapters/999999/edit')->assertNotFound();
        $this->get('/progress')->assertOk()->assertSee('href="'.route('admin').'"', false);
    }

    public function test_admin_changes_publish_on_public_site_and_validate_questions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get('/admin/chapters')->assertOk();
        $state = $response->viewData('state');
        $id = $state['chapters'][0]['id'];
        $state['chapters'][0]['title'] = 'Updated chapter';
        $state['chapters'][0]['content'] = '<p onclick="alert(1)">Updated content</p><script>alert(1)</script>';
        $saved = $this->putJson('/admin/state', $state)->assertOk();
        $this->assertDatabaseHas('chapters', ['id' => $id, 'title' => 'Updated chapter']);
        $this->assertStringNotContainsString('onclick', Chapter::find($id)->content);
        $this->get('/learn/history/0')->assertOk()->assertSee('Updated chapter');
        $state = $saved->json();
        $state['tests'][0]['qs'][0]['c'] = 9;
        $this->putJson('/admin/state', $state)->assertUnprocessable();
        $state = $saved->json();
        $state['chapters'][0]['status'] = 'draft';
        $this->putJson('/admin/state', $state)->assertOk();
        $quiz = Quiz::where('chapter_id', $id)->firstOrFail();
        $this->postJson('/quizzes/'.$quiz->id.'/answer', ['question' => 0, 'answer' => 0])->assertNotFound();
    }

    public function test_guest_and_student_cannot_write_admin_data(): void
    {
        $this->putJson('/admin/state', [])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->putJson('/admin/state', [])->assertForbidden();
    }

    public function test_admin_can_create_and_delete_a_chapter_with_multiple_quizzes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $state = $this->actingAs($admin)->get('/admin')->viewData('state');
        $chapterId = max(array_column($state['chapters'], 'id')) + 1;
        $quizId = max(array_column($state['tests'], 'id')) + 1;
        $state['chapters'][] = ['id' => $chapterId, 'title' => 'New lesson', 'subject' => 'New subject', 'lessons' => 1, 'desc' => 'Lesson description', 'content' => '<p>Learn here</p>', 'status' => 'published'];
        foreach ([$quizId, $quizId + 1] as $id) {
            $state['tests'][] = ['id' => $id, 'ch' => $chapterId, 'title' => 'Test '.$id, 'dur' => 10, 'pass' => 50, 'qs' => [['q' => '2 + 2?', 'o' => ['1', '2', '3', '4'], 'c' => 3]]];
        }
        $state = $this->putJson('/admin/state', $state)->assertOk()->json();
        $this->get('/subject/new-subject')->assertOk();
        $this->get('/quiz/new-subject/0?test='.($quizId + 1))->assertOk()->assertViewHas('quizIds', fn ($ids) => $ids['new-subject:0'] === $quizId + 1);
        $this->putJson('/admin/state', [...$state, 'version' => 'stale'])->assertStatus(409);
        $state['chapters'] = array_values(array_filter($state['chapters'], fn ($c) => $c['id'] !== $chapterId));
        $state['tests'] = array_values(array_filter($state['tests'], fn ($t) => $t['ch'] !== $chapterId));
        $this->putJson('/admin/state', $state)->assertOk();
        $this->assertDatabaseMissing('chapters', ['id' => $chapterId]);
        $this->assertDatabaseMissing('quizzes', ['id' => $quizId]);
    }

    public function test_scores_are_calculated_on_server_and_attempts_are_persisted(): void
    {
        $quiz = Quiz::firstOrFail();
        $answers = array_column($quiz->questions, 'c');
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/quizzes/'.$quiz->id.'/attempts', ['answers' => $answers, 'seconds' => 25, 'score' => 0])->assertOk()->assertJson(['percentage' => 100, 'passed' => true]);
        $this->assertDatabaseHas('attempts', ['quiz_id' => $quiz->id, 'user_id' => $user->id, 'percentage' => 100]);
        $this->postJson('/quizzes/'.$quiz->id.'/attempts', ['answers' => [], 'seconds' => 2])->assertUnprocessable();
        $this->assertSame(1, Attempt::count());
    }

    public function test_blocked_users_cannot_login_or_submit_attempts(): void
    {
        $user = User::factory()->create(['status' => 'blocked', 'password' => 'StrongPassword123']);
        $this->postJson('/account/login', ['email' => $user->email, 'password' => 'StrongPassword123'])->assertUnprocessable();
        $quiz = Quiz::firstOrFail();
        $this->actingAs($user)->postJson('/quizzes/'.$quiz->id.'/attempts', ['answers' => array_column($quiz->questions, 'c'), 'seconds' => 5])->assertForbidden();
    }

    public function test_guests_can_answer_25_questions_and_then_need_an_account(): void
    {
        $quiz = Quiz::firstOrFail();
        $url = '/quizzes/'.$quiz->id.'/answer';
        for ($i = 0; $i < 25; $i++) {
            $this->postJson($url, ['question' => 0, 'answer' => 0])->assertOk()->assertJsonPath('guest_remaining', 24 - $i);
        }
        $this->postJson($url, ['question' => 1, 'answer' => 0])->assertForbidden()->assertJsonPath('code', 'ACCOUNT_REQUIRED');
        $this->postJson('/quizzes/'.$quiz->id.'/attempts', ['answers' => array_column($quiz->questions, 'c'), 'seconds' => 10])->assertForbidden();
        $this->actingAs(User::factory()->create())->postJson($url, ['question' => 1, 'answer' => 0])->assertOk();
    }

    public function test_guest_quiz_submission_does_not_count_answered_questions_twice(): void
    {
        $quiz = Quiz::firstOrFail();
        $answers = array_column($quiz->questions, 'c');
        $this->withSession(['guest_questions_used' => 20]);
        foreach ($answers as $index => $answer) {
            $this->postJson('/quizzes/'.$quiz->id.'/answer', ['question' => $index, 'answer' => $answer])->assertOk();
        }
        $this->postJson('/quizzes/'.$quiz->id.'/attempts', ['answers' => $answers, 'seconds' => 10])->assertOk()->assertSessionHas('guest_questions_used', 25);
        $this->postJson('/quizzes/'.$quiz->id.'/answer', ['question' => 0, 'answer' => 0])->assertForbidden();
    }
}
