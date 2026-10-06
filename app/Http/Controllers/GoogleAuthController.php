<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return $this->failure('Google sign in is not configured yet. Please use email and password.');
        }
        $state = Str::random(64);
        $verifier = Str::random(80);
        $request->session()->put('google_oauth', ['state' => $state, 'verifier' => $verifier, 'expires' => now()->addMinutes(10)->timestamp]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'), 'redirect_uri' => $this->callbackUrl(),
            'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256', 'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request): RedirectResponse
    {
        $oauth = $request->session()->pull('google_oauth');
        if (! is_array($oauth) || ! is_string($request->query('state')) || ! hash_equals($oauth['state'], $request->query('state')) || $oauth['expires'] < now()->timestamp) {
            return $this->failure('Google sign in expired. Please try again.');
        }
        if ($request->has('error') || ! is_string($request->query('code'))) {
            return $this->failure('Google sign in was cancelled. Please try again.');
        }
        try {
            $token = Http::asForm()->connectTimeout(10)->timeout(20)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'), 'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->callbackUrl(), 'grant_type' => 'authorization_code',
                'code' => $request->query('code'), 'code_verifier' => $oauth['verifier'],
            ]);
            if (! $token->successful() || ! is_string($token->json('access_token')) || $token->json('access_token') === '') {
                return $this->failure('Google sign in failed. Please try again.');
            }
            $response = Http::withToken($token->json('access_token'))->connectTimeout(10)->timeout(20)->get('https://openidconnect.googleapis.com/v1/userinfo');
            $profile = $response->json();
            if (! $response->successful() || ! is_array($profile) || Validator::make($profile, ['sub' => 'required|string|max:255', 'email' => 'required|email|max:255', 'email_verified' => 'accepted'])->fails()) {
                return $this->failure('Google could not verify your email address.');
            }
            $user = DB::transaction(function () use ($profile): ?User {
                $user = User::where('google_id', $profile['sub'])->lockForUpdate()->first();
                if (! $user) {
                    $user = User::where('email', $profile['email'])->lockForUpdate()->first();
                    if ($user && ($user->google_id || (! str_ends_with(strtolower($profile['email']), '@gmail.com') && empty($profile['hd'])))) {
                        return null;
                    }
                }
                if ($user && $user->status !== 'active') {
                    return null;
                }
                if (! $user) {
                    $user = new User;
                    $user->fill(['name' => Str::limit(is_string($profile['name'] ?? null) ? $profile['name'] : $profile['email'], 100, ''), 'email' => $profile['email'], 'password' => Str::random(64)]);
                }
                $user->google_id = $profile['sub'];
                if ($user->email === $profile['email'] && ! $user->hasVerifiedEmail()) {
                    $user->email_verified_at = now();
                }
                $user->save();

                return $user;
            });
            if (! $user) {
                return $this->failure('This account cannot use Google sign in. Please use email and password or contact support.');
            }
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route($user->role === 'admin' ? 'admin' : 'home');
        } catch (Throwable) {
            return $this->failure('Google sign in is temporarily unavailable. Please try again.');
        }
    }

    private function callbackUrl(): string
    {
        return config('services.google.redirect') ?: route('google.callback');
    }

    private function failure(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('google_error', $message);
    }
}
