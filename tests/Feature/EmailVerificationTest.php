<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_without_logging_in(): void
    {
        Notification::fake();
        $this->postJson('/account/register', ['name' => 'Student', 'email' => 'student@example.com', 'password' => 'StrongPassword123'])
            ->assertCreated()->assertJsonPath('code', 'VERIFICATION_REQUIRED');
        $user = User::where('email', 'student@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function test_signed_link_verifies_email_and_allows_login(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'StrongPassword123']);
        $credentials = ['email' => $user->email, 'password' => 'StrongPassword123'];
        $this->postJson('/account/login', $credentials)->assertForbidden()->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
        $this->assertGuest();
        $url = (new VerifyEmail)->toMail($user)->actionUrl;
        $this->get($url)->assertRedirect(route('login', ['verified' => 1]));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();
        $this->postJson('/account/login', $credentials)->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_unsigned_expired_and_wrong_email_links_cannot_verify(): void
    {
        $user = User::factory()->unverified()->create();
        $parameters = ['id' => $user->id, 'hash' => sha1($user->email)];
        $this->get(route('verification.verify', $parameters))->assertForbidden();
        $this->get(URL::temporarySignedRoute('verification.verify', now()->subMinute(), $parameters))->assertForbidden();
        $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), [...$parameters, 'hash' => sha1('wrong@example.com')]))->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function test_resending_requires_correct_credentials_and_never_logs_in(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['password' => 'StrongPassword123']);
        $this->postJson(route('verification.send'), ['email' => $user->email, 'password' => 'incorrect'])->assertUnprocessable();
        Notification::assertNothingSent();
        $this->postJson(route('verification.send'), ['email' => $user->email, 'password' => 'StrongPassword123'])->assertOk();
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertGuest();
    }

    public function test_unverified_admin_can_login_without_verification_and_no_verification_email_is_sent(): void
    {
        Notification::fake();
        $admin = User::factory()->unverified()->create(['role' => 'admin', 'password' => 'StrongPassword123']);
        $credentials = ['email' => $admin->email, 'password' => 'StrongPassword123'];
        $this->postJson(route('verification.send'), $credentials)->assertOk()->assertJsonPath('message', 'Admin accounts do not require email verification. You can log in.');
        Notification::assertNothingSent();
        $this->postJson('/account/login', $credentials)->assertOk()->assertJsonPath('redirect', route('admin'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin'))->assertOk();
        $this->assertFalse($admin->fresh()->hasVerifiedEmail());
    }

    public function test_admin_verification_exemption_does_not_bypass_password_or_blocked_status(): void
    {
        $admin = User::factory()->unverified()->create(['role' => 'admin', 'password' => 'StrongPassword123']);
        $this->postJson('/account/login', ['email' => $admin->email, 'password' => 'incorrect'])->assertUnprocessable();
        $this->assertGuest();
        $admin->status = 'blocked';
        $admin->save();
        $credentials = ['email' => $admin->email, 'password' => 'StrongPassword123'];
        $this->postJson('/account/login', $credentials)->assertUnprocessable();
        $this->postJson(route('verification.send'), $credentials)->assertUnprocessable();
        $this->assertGuest();
        $this->actingAs($admin)->get(route('admin'))->assertForbidden();
    }
}
