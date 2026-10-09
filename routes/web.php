<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DailyQuizController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageSeoController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebsiteLanguageController;
use App\Http\Controllers\WordImportController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebsiteController::class, 'index'])->name('home');
Route::post('/language', [WebsiteLanguageController::class, 'update'])->name('website.language');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/blogs', [PostController::class, 'blogs'])->name('blogs.index');
Route::get('/blogs/{slug}', [PostController::class, 'show'])->name('blogs.show');
Route::get('/login', [WebsiteController::class, 'index'])->name('login');
Route::get('/register', [WebsiteController::class, 'index'])->name('register');
Route::get('/weekly-leaderboard', [LearningController::class, 'leaderboard'])->name('learning.leaderboard');
Route::middleware('auth')->group(function (): void {
    Route::get('/my-learning', [LearningController::class, 'index'])->name('learning');
    Route::post('/my-learning/preferences', [LearningController::class, 'preferences'])->name('learning.preferences');
    Route::post('/learning/bookmark', [LearningController::class, 'bookmark'])->middleware('throttle:60,1')->name('learning.bookmark');
    Route::post('/learning/report', [LearningController::class, 'report'])->middleware('throttle:10,1')->name('learning.report');
    Route::post('/learning/explanation', [LearningController::class, 'explanation'])->middleware('throttle:10,1')->name('learning.explanation');
    Route::post('/learning/sessions', [LearningController::class, 'createSession'])->middleware('throttle:10,1')->name('learning.create');
    Route::get('/practice/{id}', [LearningController::class, 'session'])->whereUuid('id')->name('learning.session');
    Route::post('/practice/{id}/start', [LearningController::class, 'start'])->whereUuid('id')->middleware('throttle:20,1')->name('learning.start');
    Route::post('/practice/{id}', [LearningController::class, 'submit'])->whereUuid('id')->middleware('throttle:20,1')->name('learning.submit');
    Route::get('/admin/question-reports', [LearningController::class, 'reports'])->name('learning.reports');
    Route::post('/admin/question-reports/{id}', [LearningController::class, 'updateReport'])->whereNumber('id')->name('learning.report.update');
});
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->middleware(['guest', 'throttle:10,1'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware(['guest', 'throttle:10,1'])->name('google.callback')->block(10, 10);
Route::get('/subject/{subject}', [WebsiteController::class, 'index'])->name('subject');
Route::get('/learn/{subject}/{chapter}', [WebsiteController::class, 'index'])->whereNumber('chapter')->name('learn');
Route::get('/quiz/{subject}/{chapter}', [WebsiteController::class, 'index'])->whereNumber('chapter')->name('quiz');
Route::get('/quiz/{subject}/{quiz}-{slug}', [WebsiteController::class, 'quizPage'])->whereNumber('quiz')->name('quiz.show');
Route::post('/account/register', [AccountController::class, 'register'])->middleware('throttle:10,1');
Route::post('/account/login', [AccountController::class, 'login'])->middleware('throttle:10,1');
Route::get('/email/verify/{id}/{hash}', [AccountController::class, 'verify'])->whereNumber('id')->middleware(['signed', 'throttle:10,1'])->name('verification.verify');
Route::post('/email/verification-notification', [AccountController::class, 'resendVerification'])->middleware('throttle:3,1')->name('verification.send');
Route::post('/account/logout', [AccountController::class, 'logout'])->name('logout');
Route::post('/quizzes/{quiz}/answer', [WebsiteController::class, 'answer'])->middleware('throttle:120,1')->block(10, 10);
Route::post('/quizzes/{quiz}/attempts', [WebsiteController::class, 'attempt'])->middleware('throttle:30,1')->block(10, 10);
Route::get('/daily-quiz', [DailyQuizController::class, 'index'])->name('daily')->block(10, 10);
Route::post('/daily-quiz', [DailyQuizController::class, 'submit'])->name('daily.submit')->middleware('throttle:10,1')->block(10, 10);
foreach (['daily', 'weekly', 'monthly'] as $period) {
    Route::get('/'.$period.'-quiz/sets/quiz-set-{set}', [DailyQuizController::class, 'index'])->defaults('period', $period)->whereNumber('set')->name($period.'.set')->block(10, 10);
    Route::post('/'.$period.'-quiz/sets/quiz-set-{set}', [DailyQuizController::class, 'submit'])->defaults('period', $period)->whereNumber('set')->middleware('throttle:10,1')->name($period.'.set.submit')->block(10, 10);
    Route::get('/'.$period.'-quiz/sets/{set}', fn (string $set): RedirectResponse => redirect()->route($period.'.set', ['set' => $set], 301))->whereNumber('set');
    Route::post('/'.$period.'-quiz/sets/{set}', [DailyQuizController::class, 'submit'])->defaults('period', $period)->whereNumber('set')->middleware('throttle:10,1')->block(10, 10);
}
foreach (['weekly', 'monthly'] as $period) {
    Route::get('/'.$period.'-quiz', [DailyQuizController::class, 'index'])->defaults('period', $period)->name($period)->block(10, 10);
    Route::post('/'.$period.'-quiz', [DailyQuizController::class, 'submit'])->defaults('period', $period)->name($period.'.submit')->middleware('throttle:10,1')->block(10, 10);
}
Route::view('/upcoming', 'website.upcoming')->name('upcoming');
Route::get('/site-logo/{filename}', [SiteSettingsController::class, 'logo'])->where('filename', '[A-Za-z0-9]+\.(jpg|jpeg|png|webp|gif)')->name('site.logo');
Route::get('/site-favicon/{filename}', [SiteSettingsController::class, 'favicon'])->where('filename', '[A-Za-z0-9]+\.(ico|jpg|jpeg|png|webp|gif)')->name('site.favicon');
Route::get('/exams/{exam}', [PageController::class, 'exam'])->name('exam');
Route::get('/progress', fn (Request $request) => app(PageController::class)->show($request, 'progress'))->middleware('auth')->name('progress');
Route::get('/feedback', fn (Request $request) => app(PageController::class)->show($request, 'feedback'))->name('feedback');
Route::post('/feedback', [PageController::class, 'feedback'])->middleware('throttle:5,1')->name('feedback.send');
Route::post('/contact', [PageController::class, 'contact'])->middleware('throttle:5,1')->name('contact.send');
Route::post('/forgot-password', [PageController::class, 'forgot'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [PageController::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [PageController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
Route::get('/{page}', [PageController::class, 'show'])->whereIn('page', ['about', 'contact', 'help', 'careers', 'privacy', 'terms', 'cookies', 'leaderboard', 'forgot-password'])->name('page');
Route::middleware('auth')->group(function () {
    Route::post('/admin/import', [WordImportController::class, 'store'])->middleware('throttle:10,1')->name('admin.import.store');
    Route::get('/admin/seo', [PageSeoController::class, 'index'])->name('admin.seo.index');
    Route::put('/admin/seo', [PageSeoController::class, 'update'])->name('admin.seo.update');
    Route::get('/admin/posts', [PostController::class, 'index'])->name('admin.posts.index');
    Route::get('/admin/posts/create', [PostController::class, 'create'])->name('admin.posts.create');
    Route::post('/admin/posts', [PostController::class, 'store'])->name('admin.posts.store');
    Route::get('/admin/posts/{post}/edit', [PostController::class, 'edit'])->name('admin.posts.edit');
    Route::put('/admin/posts/{post}', [PostController::class, 'update'])->name('admin.posts.update');
    Route::delete('/admin/posts/{post}', [PostController::class, 'destroy'])->name('admin.posts.destroy');
    Route::get('/admin/tests/create', [AdminController::class, 'testPage'])->name('admin.tests.create');
    Route::get('/admin/tests/{quiz}/edit', [AdminController::class, 'testPage'])->whereNumber('quiz')->name('admin.tests.edit');
    Route::get('/admin/chapters/create', [AdminController::class, 'chapterPage'])->name('admin.chapters.create');
    Route::get('/admin/chapters/{chapter}/edit', [AdminController::class, 'chapterPage'])->name('admin.chapters.edit');
    Route::get('/admin/chapters/{chapter}', [AdminController::class, 'chapterPage'])->whereNumber('chapter')->name('admin.chapters.show');
    Route::put('/admin/state', [AdminController::class, 'save'])->name('admin.save');
    Route::post('/admin/practice/generate', [AdminController::class, 'generatePractice'])->middleware('throttle:6,1')->name('admin.practice.generate');
    Route::put('/admin/settings', [SiteSettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/admin/{page?}', [AdminController::class, 'index'])->name('admin');
});

Route::get('/{category}/{chapterSlug}', [WebsiteController::class, 'historyChapter'])
    ->whereIn('category', ['ancient-history', 'medieval-history', 'modern-history'])
    ->where('chapterSlug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('chapter');
