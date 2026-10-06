<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'client', 'services.google.client_secret' => 'secret']);
        Http::preventStrayRequests();
    }

    public function test_redirect_uses_state_and_pkce_and_callback_creates_verified_student(): void
    {
        $response = $this->get(route('google.redirect'))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('openid email profile', $query['scope']);
        $this->fakeProfile();
        $this->get(route('google.callback', ['state' => $query['state'], 'code' => 'code']))->assertRedirect(route('home'));
        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('student', $user->role);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame('google-user', $user->google_id);
        $this->assertFalse(session()->has('google_oauth'));
    }

    public function test_existing_admin_is_linked_without_duplicate_account_and_keeps_admin_access(): void
    {
        $admin = User::factory()->unverified()->create(['email' => 'learner@gmail.com', 'role' => 'admin']);
        $this->fakeProfile();
        $this->completeGoogleLogin()->assertRedirect(route('admin'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin'))->assertOk();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_blocked_accounts_cannot_login_with_google(): void
    {
        User::factory()->create(['email' => 'learner@gmail.com', 'status' => 'blocked']);
        $this->fakeProfile();
        $this->completeGoogleLogin()->assertRedirect(route('login'))->assertSessionHas('google_error');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['google_id' => 'google-user']);
    }

    public function test_invalid_state_does_not_contact_google(): void
    {
        $this->get(route('google.callback', ['state' => 'forged', 'code' => 'code']))->assertRedirect(route('login'));
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_google_unverified_email_leaves_user_logged_out(): void
    {
        $this->fakeProfile(['email_verified' => false]);
        $this->completeGoogleLogin()->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_does_not_link_existing_third_party_email(): void
    {
        User::factory()->create(['email' => 'learner@example.com']);
        $this->fakeProfile(['email' => 'learner@example.com']);
        $this->completeGoogleLogin()->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['google_id' => 'google-user']);
    }

    public function test_unconfigured_google_sign_in_displays_message(): void
    {
        config(['services.google.client_id' => null]);
        $this->get(route('google.redirect'))->assertRedirect(route('login'))->assertSessionHas('google_error');
        $this->get(route('login'))->assertOk()->assertSee('GOOGLE_LOGIN_URL');
        Http::assertNothingSent();
    }

    private function completeGoogleLogin(): TestResponse
    {
        $this->get(route('google.redirect'));

        return $this->get(route('google.callback', ['state' => session('google_oauth.state'), 'code' => 'code']));
    }

    private function fakeProfile(array $overrides = []): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([...['sub' => 'google-user', 'email' => 'learner@gmail.com', 'email_verified' => true, 'name' => 'Learner'], ...$overrides]),
        ]);
    }
}
