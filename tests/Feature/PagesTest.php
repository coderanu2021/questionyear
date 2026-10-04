<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_information_pages_exams_daily_quiz_and_contact(): void
    {
        $this->seed(CurriculumSeeder::class);
        foreach (['/about', '/contact', '/help', '/careers', '/privacy', '/terms', '/cookies', '/leaderboard', '/forgot-password', '/exams/neet'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/daily-quiz')->assertOk()->assertSee('Daily quiz');
        $this->get('/progress')->assertRedirect('/login');
        $this->post('/contact', ['name' => 'Student', 'email' => 'student@example.com', 'message' => 'Please add more chapter quizzes.'])->assertRedirect();
        $this->assertDatabaseHas('messages', ['email' => 'student@example.com']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/messages')->assertOk();
        $this->get('/progress')->assertOk();
    }

    public function test_reset_link_uses_laravel_password_broker(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        Notification::assertSentTo($user, ResetPassword::class);
        $this->post('/reset-password', ['email' => $user->email, 'token' => 'invalid', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('email');
    }
}
