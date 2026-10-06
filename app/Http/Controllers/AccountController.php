<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:8|max:255']);
        $user = User::create($data);
        $sent = $this->sendVerification($user);

        return response()->json([
            'code' => 'VERIFICATION_REQUIRED',
            'message' => $sent ? 'Account created. Check your email and verify your address before logging in.' : 'Account created, but the verification email could not be sent. Please try resending it.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'nullable|boolean']);
        $needsVerification = false;
        if (! Auth::attemptWhen(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active'], function (User $user) use (&$needsVerification): bool {
            $needsVerification = $user->role !== 'admin' && ! $user->hasVerifiedEmail();

            return ! $needsVerification;
        }, $data['remember'] ?? false)) {
            if ($needsVerification) {
                return response()->json(['code' => 'EMAIL_NOT_VERIFIED', 'message' => 'Verify your email address before logging in. You can resend the verification email below.'], 403);
            }
            throw ValidationException::withMessages(['email' => 'Incorrect email or password, or account is blocked.']);
        }
        $request->session()->regenerate();

        return response()->json([
            ...$request->user()->only('name', 'email'),
            'redirect' => $request->user()->role === 'admin' ? route('admin') : $request->session()->pull('url.intended', route('home')),
        ]);
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('login', ['verified' => 1]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', $data['email'])->where('status', 'active')->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Incorrect email or password, or account is blocked.']);
        }
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Admin accounts do not require email verification. You can log in.']);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email is already verified. You can log in.']);
        }
        if (! $this->sendVerification($user)) {
            return response()->json(['message' => 'The verification email could not be sent. Please try again later.'], 503);
        }

        return response()->json(['message' => 'Verification email sent. Check your inbox and spam folder.']);
    }

    private function sendVerification(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson() ? response()->json(['message' => 'Logged out']) : redirect()->route('home');
    }
}
