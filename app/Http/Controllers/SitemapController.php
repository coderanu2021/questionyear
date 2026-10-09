<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];
        foreach (['home', 'learning.leaderboard'] as $name) {
            $urls[route($name)] = null;
        }
        foreach (['about', 'contact', 'help', 'careers', 'privacy', 'terms', 'cookies', 'leaderboard'] as $page) {
            $urls[route('page', $page)] = null;
        }
        foreach (['neet', 'jee', 'cbse-board', 'state-psc'] as $exam) {
            $urls[route('exam', $exam)] = null;
        }
        $subjects = Subject::with(['chapters' => fn ($query) => $query->where('status', 'published')->orderBy('id'), 'chapters.quizzes'])->orderBy('id')->get();
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
        foreach (Post::where('type', 'blog')->where('status', 'published')->where('published_at', '<=', now())->get() as $post) {
            $urls[route('blogs.index')] = null;
            $urls[route('blogs.show', $post->slug)] = $post->updated_at;
        }
        foreach (Quiz::published()->whereNotNull('subject_id')->with('subject')->get() as $quiz) {
            $urls[route('subject', $quiz->subject->slug)] = $quiz->subject->updated_at;
            $urls[$quiz->publicUrl()] = $quiz->updated_at;
        }
        foreach (DB::table('practice_sets')->whereIn('period', ['daily', 'weekly', 'monthly'])->orderBy('id')->get() as $set) {
            if (count(json_decode($set->questions, true) ?? []) > 0) {
                $urls[route($set->period)] = null;
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
