<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:8|max:255']);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($user->only('name', 'email'), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'nullable|boolean']);
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active'], $data['remember'] ?? false)) {
            throw ValidationException::withMessages(['email' => 'Incorrect email or password, or account is blocked.']);
        }
        $request->session()->regenerate();

        return response()->json([
            ...$request->user()->only('name', 'email'),
            'redirect' => $request->user()->role === 'admin' ? route('admin') : route('home'),
        ]);
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson() ? response()->json(['message' => 'Logged out']) : redirect()->route('home');
    }
}
