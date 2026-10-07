<?php

namespace App;

use App\Models\Chapter;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageSeo
{
    /** @return array<string, array{label: string, group: string, url: ?string}> */
    public static function pages(): array
    {
        $pages = ['defaults' => ['label' => 'Website SEO defaults', 'group' => 'Defaults', 'url' => null]];
        foreach (['home' => 'Homepage', 'login' => 'Login', 'register' => 'Register', 'daily' => 'Daily quiz', 'weekly' => 'Weekly quiz', 'monthly' => 'Monthly quiz', 'blogs.index' => 'Blogs', 'upcoming' => 'Coming soon', 'feedback' => 'Feedback', 'progress' => 'My progress', 'learning' => 'My learning', 'learning.leaderboard' => 'Weekly leaderboard', 'learning.reports' => 'Question reports', 'admin.seo.index' => 'Page SEO admin'] as $name => $label) {
            $pages['route:'.$name] = ['label' => $label, 'group' => 'Pages', 'url' => route($name)];
        }
        foreach (['about', 'contact', 'help', 'careers', 'privacy', 'terms', 'cookies', 'leaderboard', 'forgot-password'] as $page) {
            $pages['page:'.$page] = ['label' => ucwords(str_replace('-', ' ', $page)), 'group' => 'Pages', 'url' => route('page', ['page' => $page])];
        }
        foreach (['subject' => 'Subject pages', 'learn' => 'Chapter pages', 'quiz.show' => 'Quiz detail pages', 'blogs.show' => 'Blog detail pages', 'exam' => 'Exam pages', 'learning.session' => 'Practice session pages', 'password.reset' => 'Reset password pages', 'admin.tests.create' => 'Create test admin', 'admin.tests.edit' => 'Edit test admin', 'admin.chapters.create' => 'Create chapter admin', 'admin.chapters.edit' => 'Edit chapter admin', 'admin.chapters.show' => 'Chapter preview admin', 'admin.posts.create' => 'Create post admin', 'admin.posts.edit' => 'Edit post admin'] as $name => $label) {
            $pages['route:'.$name] = ['label' => $label, 'group' => 'Page type defaults', 'url' => null];
        }
        $pages['error:404'] = ['label' => 'Page not found (404)', 'group' => 'Pages', 'url' => null];
        foreach (['upsc', 'ssc', 'banking', 'baking', 'railways', 'railway', 'neet', 'jee', 'cbse-board', 'state-psc'] as $exam) {
            $pages['exam:'.$exam] = ['label' => strtoupper(str_replace('-', ' ', $exam)), 'group' => 'Exams', 'url' => route('exam', ['exam' => $exam])];
        }
        foreach (['dashboard', 'chapters', 'tests', 'posts', 'users', 'analytics', 'messages', 'practice', 'settings'] as $page) {
            $url = $page === 'posts' ? route('admin.posts.index') : route('admin', ['page' => $page]);
            $pages['admin:'.$page] = ['label' => ucfirst($page).' admin', 'group' => 'Admin pages', 'url' => $url];
        }
        foreach (Subject::orderBy('name')->get() as $subject) {
            $pages['subject:'.$subject->id] = ['label' => $subject->name, 'group' => 'Subjects', 'url' => route('subject', $subject->slug)];
        }
        $chapterIndexes = [];
        foreach (Chapter::with('subject')->orderBy('id')->get() as $chapter) {
            if (in_array($chapter->subject->slug, ['current-affairs', 'general-knowledge'], true)) {
                continue;
            }
            $index = $chapterIndexes[$chapter->subject_id] ?? 0;
            $pages['chapter:'.$chapter->id] = ['label' => $chapter->subject->name.' / '.$chapter->title, 'group' => 'Chapters', 'url' => $chapter->status === 'published' ? $chapter->readingUrl($index) : null];
            if ($chapter->status === 'published') {
                $chapterIndexes[$chapter->subject_id] = $index + 1;
            }
        }
        foreach (Quiz::with('chapter.subject')->orderBy('title')->get() as $quiz) {
            $pages['quiz:'.$quiz->id] = ['label' => $quiz->learningSubject()->name.' / '.$quiz->title, 'group' => 'Quizzes', 'url' => $quiz->isPublished() ? $quiz->publicUrl() : null];
        }
        foreach (Post::orderBy('title')->get() as $post) {
            $pages['post:'.$post->id] = ['label' => $post->title, 'group' => ucfirst($post->type).' posts', 'url' => $post->type === 'blog' && $post->status === 'published' ? route('blogs.show', $post->slug) : null];
        }

        return $pages;
    }

    /**
     * @param  array{title: string, description: string, keywords: ?string}  $defaults
     * @param  array{seoChapter?: ?Chapter, post?: ?Post, errorPage?: ?string}  $context
     * @return array{title: string, description: string, keywords: ?string}
     */
    public static function resolve(Request $request, array $defaults, array $context = []): array
    {
        $routeName = $request->route()?->getName();
        $keys = ['defaults', 'route:'.($routeName === 'chapter' ? 'learn' : $routeName)];
        if (isset($context['errorPage'])) {
            $keys = ['defaults', 'error:'.$context['errorPage']];
        } elseif ($routeName === 'page') {
            $keys[] = 'page:'.$request->route('page');
        } elseif ($routeName === 'exam') {
            $keys[] = 'exam:'.$request->route('exam');
        } elseif ($routeName === 'upcoming' && is_string($request->query('exam'))) {
            $keys[] = 'exam:'.$request->query('exam');
        } elseif ($routeName === 'admin') {
            $keys[] = 'admin:'.($request->route('page') ?: 'dashboard');
        } elseif ($routeName === 'admin.posts.index') {
            $keys[] = 'admin:posts';
        } elseif ($routeName === 'blogs.show' && isset($context['post'])) {
            $keys[] = 'post:'.$context['post']->id;
        } elseif ($routeName === 'quiz.show' && $request->route('quiz') instanceof Quiz) {
            $keys[] = 'quiz:'.$request->route('quiz')->id;
        } elseif (in_array($routeName, ['chapter', 'learn'], true) && isset($context['seoChapter'])) {
            $keys[] = 'chapter:'.$context['seoChapter']->id;
        } elseif ($routeName === 'subject') {
            $keys[] = 'subject:'.Subject::where('slug', $request->route('subject'))->value('id');
        }
        $stored = DB::table('page_seo')->whereIn('page_key', $keys)->get()->keyBy('page_key');
        foreach ($keys as $key) {
            foreach (['title', 'description', 'keywords'] as $field) {
                $value = $stored->get($key)?->{'meta_'.$field};
                if (is_string($value) && trim($value) !== '') {
                    $defaults[$field] = $value;
                }
            }
        }

        return $defaults;
    }
}
