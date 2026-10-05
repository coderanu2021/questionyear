<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DailyQuizController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebsiteController::class, 'index'])->name('home');
Route::get('/login', [WebsiteController::class, 'index'])->name('login');
Route::get('/register', [WebsiteController::class, 'index'])->name('register');
Route::get('/subject/{subject}', [WebsiteController::class, 'index'])->name('subject');
Route::get('/learn/{subject}/{chapter}', [WebsiteController::class, 'index'])->whereNumber('chapter')->name('learn');
Route::get('/quiz/{subject}/{chapter}', [WebsiteController::class, 'index'])->whereNumber('chapter')->name('quiz');
Route::post('/account/register', [AccountController::class, 'register'])->middleware('throttle:10,1');
Route::post('/account/login', [AccountController::class, 'login'])->middleware('throttle:10,1');
Route::post('/account/logout', [AccountController::class, 'logout'])->name('logout');
Route::post('/quizzes/{quiz}/answer', [WebsiteController::class, 'answer'])->middleware('throttle:120,1')->block(10, 10);
Route::post('/quizzes/{quiz}/attempts', [WebsiteController::class, 'attempt'])->middleware('throttle:30,1')->block(10, 10);
Route::get('/daily-quiz', [DailyQuizController::class, 'index'])->name('daily')->block(10, 10);
Route::post('/daily-quiz', [DailyQuizController::class, 'submit'])->name('daily.submit')->middleware('throttle:10,1')->block(10, 10);
Route::view('/upcoming', 'website.upcoming')->name('upcoming');
Route::get('/site-logo/{filename}', [SiteSettingsController::class, 'logo'])->where('filename', '[A-Za-z0-9]+\.(jpg|jpeg|png|webp|gif)')->name('site.logo');
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
    Route::get('/admin/chapters/create', [AdminController::class, 'chapterPage'])->name('admin.chapters.create');
    Route::get('/admin/chapters/{chapter}/edit', [AdminController::class, 'chapterPage'])->name('admin.chapters.edit');
    Route::get('/admin/chapters/{chapter}', [AdminController::class, 'chapterPage'])->whereNumber('chapter')->name('admin.chapters.show');
    Route::put('/admin/state', [AdminController::class, 'save'])->name('admin.save');
    Route::put('/admin/settings', [SiteSettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/admin/{page?}', [AdminController::class, 'index'])->name('admin');
});

Route::get('/{category}/{chapterSlug}', [WebsiteController::class, 'historyChapter'])
    ->whereIn('category', ['ancient-history', 'medieval-history', 'modern-history'])
    ->where('chapterSlug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('chapter');
