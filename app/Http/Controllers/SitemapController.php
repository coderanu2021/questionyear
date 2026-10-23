<?php

namespace App\Http\Controllers;

use App\Models\JobNotification;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use App\WebsiteNavigation;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];
        foreach (['home', 'learning.leaderboard', 'notifications.index', 'jobs.index'] as $name) {
            $urls[route($name)] = null;
        }
        foreach (array_keys(ExamNotificationController::CATEGORIES) as $category) {
            if ($category !== 'all') {
                $urls[route('jobs.category', ['category' => $category])] = null;
            }
        }
        foreach (['10th-pass', '12th-pass', 'graduate', 'postgraduate', 'diploma', 'other'] as $level) {
            $urls[route('jobs.qualification', ['level' => $level])] = null;
        }
        foreach (JobNotification::query()->whereIn('status', ['published', 'closed'])->whereNotNull('official_url')->get() as $notification) {
            $urls[route('job-notices.show', ['year' => $notification->created_at->format('Y'), 'slug' => $notification->slug])] = $notification->updated_at;
            if ($notification->state) {
                $urls[route('jobs.state', ['state' => Str::slug($notification->state)])] = null;
            }
            if ($notification->organization) {
                $urls[route('jobs.department', ['department' => Str::slug($notification->organization)])] = null;
            }
        }
        foreach (['about', 'contact', 'help', 'careers', 'privacy', 'terms', 'cookies', 'leaderboard'] as $page) {
            $urls[route('page', $page)] = null;
        }
        foreach (['exams.index', 'practice.index', 'current-affairs'] as $name) {
            $urls[route($name)] = null;
        }
        foreach (app(WebsiteNavigation::class)->exams() as $exam) {
            $urls[$exam['url']] = null;
        }
        $subjects = Subject::with(['chapters' => fn ($query) => $query->where('status', 'published')->orderBy('id'), 'chapters.quizzes'])->orderBy('id')->get();
        foreach (['railways', ...array_keys(ExamNotificationController::NOTICES)] as $exam) {
            $urls[route('notifications.show', ['exam' => $exam])] = Carbon::parse('2026-10-14', 'Asia/Kolkata');
        }
        foreach ($subjects as $subject) {
            foreach ($subject->chapters as $index => $chapter) {
                $chapter->setRelation('subject', $subject);
                if (! in_array($subject->slug, ['current-affairs', 'general-knowledge'], true) && ($chapter->content || $chapter->notes)) {
                    $urls[route('subject', $subject->slug)] = $subject->updated_at;
                    $urls[$chapter->readingUrl($index)] = $chapter->updated_at;
                }
                foreach ($chapter->quizzes as $quiz) {
                    $quiz->setRelation('chapter', $chapter);
                    if ($quiz->isPublished()) {
                        $urls[route('subject', $subject->slug)] = $subject->updated_at;
                        $urls[$quiz->publicUrl()] = $quiz->updated_at;
                    }
                }
            }
        }
        foreach (Post::whereIn('type', ['blog', 'news'])->where('status', 'published')->where('published_at', '<=', now())->get() as $post) {
            $urls[route($post->type === 'news' ? 'news.index' : 'blogs.index')] = null;
            $urls[route($post->type === 'news' ? 'news.show' : 'blogs.show', $post->slug)] = $post->updated_at;
        }
        foreach (Quiz::published()->whereNotNull('subject_id')->with('subject')->get() as $quiz) {
            $urls[route('subject', $quiz->subject->slug)] = $quiz->subject->updated_at;
            $urls[$quiz->publicUrl()] = $quiz->updated_at;
        }
        foreach (DB::table('practice_sets')->whereIn('period', ['daily', 'weekly', 'monthly'])->orderBy('id')->get() as $set) {
            if (count(json_decode($set->questions, true) ?? []) > 0) {
                if ($set->period !== 'monthly') {
                    $urls[route($set->period)] = null;
                }
                $urls[route($set->period.'.set', ['set' => $set->id])] = Carbon::parse($set->updated_at ?? $set->created_at);
            }
        }
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($urls as $url => $updatedAt) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url);
            if ($updatedAt !== null) {
                $xml->writeElement('lastmod', $updatedAt->toAtomString());
            }
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
