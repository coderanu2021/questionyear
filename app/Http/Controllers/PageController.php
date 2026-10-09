<?php

namespace App\Http\Controllers;

use App\Mail\MessageSubmitted;
use App\Models\Attempt;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use App\SiteSettings;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class PageController extends Controller
{
    public function show(Request $request, string $page): View
    {
        $settings = SiteSettings::values();
        $pages = [
            'about' => ['About '.$settings['site_title'], $settings['about_content']],
            'help' => ['Help centre', 'Choose a subject and open a published chapter. Read its notes or select an MCQ. Your results are saved to your progress when you are logged in. Contact us if you find an incorrect question.'],
            'careers' => ['Careers', 'There are no open positions currently. Use the contact form to express interest in contributing educational content.'],
            'privacy' => ['Privacy', 'We store your name, email, securely hashed password, MCQ answers and results to provide your account and learning history. Contact form messages are stored for administrators to review.'],
            'terms' => ['Terms of use', 'Use '.$settings['site_title'].' for personal study and practice. Content is provided for learning and does not guarantee an exam result. Do not misuse accounts or submit abusive content.'],
            'cookies' => ['Cookies', $settings['site_title'].' uses a session cookie for login and request security. A remember-me cookie is used when you choose to stay logged in. Your theme preference is stored in your browser.'],
            'feedback' => ['Share your feedback', 'Help us make your learning experience better. Tell us what you liked and what we can improve.'],
            'contact' => ['Contact us', $settings['contact_description']],
            'leaderboard' => ['Leaderboard', 'Registered learners ranked by average practice score.'],
            'progress' => ['My progress', 'Your saved practice attempts and chapter results.'],
            'forgot-password' => ['Reset your password', 'Enter your account email to request a reset link.'],
        ];
        abort_unless(isset($pages[$page]), 404);
        $attempts = $page === 'progress' ? Attempt::where('user_id', $request->user()->id)->latest()->get() : collect();
        $dailyAttempts = $page === 'progress' ? DB::table('daily_quiz_attempts')->where('user_id', $request->user()->id)->orderByDesc('quiz_date')->orderByDesc('set_number')->get() : collect();
        $quizzes = Quiz::with('chapter.subject')->get()->keyBy('id');
        $leaders = $page === 'leaderboard' ? User::where('role', 'student')->where('status', 'active')->join('attempts', 'users.id', '=', 'attempts.user_id')->select('users.name')->selectRaw('AVG(attempts.percentage) as average, COUNT(*) as attempts')->groupBy('users.id', 'users.name')->orderByDesc('average')->limit(20)->get() : collect();

        return view('website.content', ['page' => $page, 'title' => $pages[$page][0], 'description' => $pages[$page][1], 'attempts' => $attempts, 'dailyAttempts' => $dailyAttempts, 'quizzes' => $quizzes, 'leaders' => $leaders]);
    }

    public function contact(Request $request): RedirectResponse
    {
        $message = Message::create($request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255', 'message' => 'required|string|min:10|max:5000']));
        $this->emailAdministrator($message);

        return back()->with('status', 'Your message has been saved. An administrator can review it.');
    }

    public function feedback(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'rating' => 'required|integer|between:1,5',
            'message' => 'required|string|min:10|max:5000',
        ]);
        $message = Message::create([...$data, 'type' => 'feedback']);
        $this->emailAdministrator($message);

        return redirect()->route('feedback')->with('status', 'Thank you! Your feedback has been submitted.');
    }

    private function emailAdministrator(Message $message): void
    {
        try {
            Mail::to(SiteSettings::values()['contact_email'])->send(new MessageSubmitted($message));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function exam(string $exam): View|RedirectResponse
    {
        if (in_array($exam, ['ssc', 'upsc', 'banking', 'baking', 'railways', 'railway'], true)) {
            return redirect()->route('upcoming', ['exam' => $exam]);
        }
        $exams = ['upsc' => ['History', 'Geography', 'Political Science', 'Economics', 'Environment'], 'ssc' => ['Mathematics', 'Reasoning', 'English', 'General Knowledge'], 'banking' => ['Mathematics', 'Reasoning', 'English', 'Economics'], 'railways' => ['Mathematics', 'Reasoning', 'Physics', 'General Knowledge'], 'neet' => ['Biology', 'Physics', 'Chemistry'], 'jee' => ['Mathematics', 'Physics', 'Chemistry'], 'cbse-board' => ['Mathematics', 'Physics', 'Chemistry', 'Biology', 'English'], 'state-psc' => ['History', 'Geography', 'Political Science', 'General Knowledge']];
        abort_unless(isset($exams[$exam]), 404);

        return view('website.content', ['page' => 'exam', 'title' => strtoupper(str_replace('-', ' ', $exam)).' practice', 'description' => 'Explore relevant subjects and available chapter MCQs.', 'subjects' => Subject::whereIn('name', $exams[$exam])->get()]);
    }

    public function forgot(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If the account exists, a password reset link has been sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('website.content', ['page' => 'reset-password', 'title' => 'Choose a new password', 'description' => 'Enter your account email and a new password.', 'token' => $token]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|string|min:8|confirmed']);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET ? redirect('/login')->with('status', __($status)) : back()->withErrors(['email' => __($status)]);
    }
}
