<?php

namespace App\Http\Controllers;

use App\Models\JobNotification;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use App\WebsiteNavigation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use XMLWriter;

class SitemapController extends Controller
{
    private const URL_LIMIT = 5000;

    public function __invoke(Request $request): Response
    {
        $urls = $this->publicUrls();
        $sitemaps = array_chunk($urls, self::URL_LIMIT, true);

        if ($request->query('page') !== null) {
            $page = filter_var($request->query('page'), FILTER_VALIDATE_INT);
            abort_if($page === false || $page < 1 || count($urls) < self::URL_LIMIT || ! isset($sitemaps[$page - 1]), 404);

            return $this->xmlResponse($this->renderUrlset($sitemaps[$page - 1]));
        }

        if (count($urls) >= self::URL_LIMIT) {
            $sitemapUrls = [];
            foreach (array_keys($sitemaps) as $index) {
                $sitemapUrls[route('sitemap', ['page' => $index + 1])] = now();
            }

            return $this->xmlResponse($this->renderSitemapIndex($sitemapUrls));
        }

        return $this->xmlResponse($this->renderUrlset($urls));
    }

    /** @return array<string, Carbon|null> */
    private function publicUrls(): array
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
        foreach (JobNotification::query()->where('status', 'published')->whereNotNull('official_url')->get() as $notification) {
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
        foreach (['exams.index', 'practice.index', 'current-affairs', 'current-affairs.period'] as $name) {
            if ($name === 'current-affairs.period') {
                foreach (['daily', 'weekly', 'monthly'] as $period) {
                    $urls[route($name, ['period' => $period])] = null;
                }

                continue;
            }
            $urls[route($name)] = null;
        }
        foreach (app(WebsiteNavigation::class)->exams() as $exam) {
            $urls[$exam['url']] = null;
        }
        $subjects = Subject::with(['chapters' => fn ($query) => $query->where('status', 'published')->orderBy('id'), 'chapters.quizzes'])->orderBy('id')->get();
        foreach (['railways', ...array_keys(ExamNotificationController::NOTICES)] as $exam) {
            $urls[route('notifications.show', ['exam' => $exam])] = null;
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
            $urls[route($post->type === 'news' ? 'news.index' : 'blogs.index')] = $post->updated_at;
            $urls[route($post->type === 'news' ? 'news.show' : 'blogs.show', $post->slug)] = $post->updated_at;
        }
        foreach (Quiz::published()->whereNotNull('subject_id')->with('subject')->get() as $quiz) {
            $urls[route('subject', $quiz->subject->slug)] = $quiz->subject->updated_at;
            $urls[$quiz->publicUrl()] = $quiz->updated_at;
        }
        foreach (DB::table('practice_sets')->whereIn('period', ['daily', 'weekly', 'monthly'])->orderBy('id')->get() as $set) {
            if (count(json_decode($set->questions, true) ?? []) > 0) {
                if ($set->period !== 'monthly') {
                    $urls[route($set->period)] = Carbon::parse($set->updated_at ?? $set->created_at);
                }
                $urls[route($set->period.'.set', ['set' => $set->id])] = Carbon::parse($set->updated_at ?? $set->created_at);
            }
        }

        return $urls;
    }

    /** @param array<string, Carbon|null> $urls */
    private function renderUrlset(array $urls): string
    {
        $xml = new XMLWriter;
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

        return $xml->outputMemory();
    }

    /** @param array<string, Carbon> $sitemaps */
    private function renderSitemapIndex(array $sitemaps): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('sitemapindex');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($sitemaps as $url => $updatedAt) {
            $xml->startElement('sitemap');
            $xml->writeElement('loc', $url);
            $xml->writeElement('lastmod', $updatedAt->toAtomString());
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
