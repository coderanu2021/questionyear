<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\JobNotification;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private function withoutScripts(string $html): string
    {
        return preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html) ?? '';
    }

    public function test_chapter_notes_are_visible_in_initial_html_with_one_h1(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create(['title' => 'Indus Valley Civilization', 'category' => 'Ancient History', 'content' => '<h1>Imported heading</h1><p>Harappa and Mohenjo-daro were important cities.</p><table><tr><td>Harappa</td><td>Punjab</td></tr></table>']);
        $response = $this->get($chapter->readingUrl(0))->assertOk();
        $html = $this->withoutScripts($response->getContent());
        $this->assertStringContainsString('<p>Harappa and Mohenjo-daro were important cities.</p>', $html);
        $this->assertStringContainsString('<h2>Imported heading</h2>', $html);
        $this->assertStringContainsString('<td>Punjab</td>', $html);
        $this->assertStringNotContainsString('<main id="learn" hidden', $html);
        $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
        $this->assertStringContainsString('Study Indus Valley Civilization with History chapter notes', $html);
    }

    public function test_legacy_notes_tables_and_summaries_are_server_rendered(): void
    {
        $chapter = Chapter::factory()->create(['content' => null, 'notes' => ['s' => [['h' => 'Important rivers', 'p' => ['Read about the rivers.'], 't' => ['h' => ['River', 'Region'], 'r' => [['Ganga', 'North India']]]]], 'sum' => ['Remember the major river systems.']]]);
        $html = $this->withoutScripts($this->get($chapter->readingUrl(0))->assertOk()->getContent());
        $this->assertStringContainsString('<th scope="col">River</th>', $html);
        $this->assertStringContainsString('Remember the major river systems.', $html);
    }

    public function test_home_and_subject_pages_have_crawlable_topic_links(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create(['title' => 'Prehistoric Period']);
        $quiz = Quiz::factory()->for($chapter)->create(['title' => 'Prehistoric MCQs']);
        $home = $this->get(route('home'))->assertOk()->assertSee('<title>Latest Govt Jobs '.now()->year.', Free GK MCQs &amp; Previous Year Questions | questionyear</title>', false);
        $this->assertStringContainsString('<h3>History</h3>', $this->withoutScripts($home->getContent()));
        $subjectPage = $this->get(route('subject', $subject->slug))->assertOk()->assertSee('History Notes & Practice MCQs');
        $html = $this->withoutScripts($subjectPage->getContent());
        $this->assertStringContainsString('<h1 id="sn">History</h1>', $html);
        $this->assertStringContainsString('href="'.$chapter->readingUrl(0).'"', $html);
        $this->assertStringContainsString('href="'.$quiz->publicUrl().'"', $html);
        $this->assertStringNotContainsString('name="robots" content="noindex', $html);
    }

    public function test_homepage_has_keyword_metadata_a_single_h1_and_server_rendered_nonzero_counts(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create(['status' => 'published']);
        Quiz::factory()->for($chapter)->create(['questions' => [['q' => 'Question?', 'o' => ['A', 'B', 'C', 'D'], 'c' => 0]]]);

        $response = $this->get(route('home'))->assertOk()
            ->assertSee('<title>Latest Govt Jobs '.now()->year.', Free GK MCQs &amp; Previous Year Questions | questionyear</title>', false)
            ->assertSee('name="description" content="Find Latest Govt Jobs, practise free GK MCQs, and explore previous year questions for competitive exams and focused exam preparation with questionyear."', false);
        $html = $this->withoutScripts($response->getContent());

        $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
        $this->assertStringContainsString('<h1>Latest Govt Jobs, Free GK MCQs &amp; Previous Year Questions</h1>', $html);
        $this->assertStringContainsString('<strong data-n="1">1</strong><span>Chapters</span>', $html);
        $this->assertStringContainsString('<strong data-n="1">1</strong><span>Subjects</span>', $html);
        $this->assertStringContainsString('<strong data-n="1">1</strong><span>MCQs ready</span>', $html);
        $this->assertStringContainsString('<strong data-n="1">1</strong><span>Practice questions</span>', $html);
    }

    public function test_structured_data_is_present_only_for_verified_page_facts(): void
    {
        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('"@type":"Organization"', $home);
        $this->assertStringContainsString('"@type":"WebSite"', $home);

        $quiz = Quiz::factory()->create(['title' => 'Verified Practice Quiz']);
        $this->get($quiz->publicUrl())->assertOk()->assertSee('"@type":"Quiz"', false);

        $post = Post::factory()->published()->create(['type' => 'news', 'title' => 'Verified exam update']);
        $this->get(route('news.show', $post->slug))->assertOk()
            ->assertSee('"@type":"NewsArticle"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);

        $notification = JobNotification::factory()->create(['status' => 'published', 'start_date' => null]);
        $jobPage = $this->get(route('job-notices.show', ['year' => $notification->created_at->format('Y'), 'slug' => $notification->slug]))->assertOk();
        $jobPage->assertDontSee('"@type":"JobPosting"', false)->assertSee('"@type":"BreadcrumbList"', false);

        $notification->update(['start_date' => today(), 'official_url' => 'https://ssc.gov.in/notice']);
        $this->get(route('job-notices.show', ['year' => $notification->created_at->format('Y'), 'slug' => $notification->slug]))
            ->assertOk()->assertSee('"@type":"JobPosting"', false);
    }

    public function test_homepage_hides_empty_counters_instead_of_rendering_zeroes(): void
    {
        $html = $this->withoutScripts($this->get(route('home'))->assertOk()->getContent());

        $this->assertStringNotContainsString('data-n="0"', $html);
        $this->assertStringNotContainsString('<strong>0</strong>', $html);
    }

    public function test_homepage_faq_answers_are_user_facing_and_have_matching_schema(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('How can I practise for SSC or UPSC?', $html);
        $this->assertStringNotContainsString('administrator publishes', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('"name":"How can I practise for SSC or UPSC?"', $html);
    }

    public function test_breadcrumb_schema_covers_public_subject_browse_quiz_and_listing_pages(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create();
        $quiz = Quiz::factory()->for($chapter)->create();

        foreach ([
            route('subject', $subject->slug),
            route('exam', 'ssc'),
            route('quiz.show', ['subject' => $subject->slug, 'quiz' => $quiz->id, 'slug' => Str::slug($quiz->title)]),
            route('daily'),
            route('blogs.index'),
            route('news.index'),
            route('page', 'about'),
            route('notifications.index'),
            route('jobs.index'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('"@type":"BreadcrumbList"', false);
        }
    }

    public function test_mcq_text_and_options_are_indexable_without_exposing_answers(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Prehistoric Period MCQ', 'questions' => [['q' => 'Which age used polished stone tools?', 'o' => ['Paleolithic', 'Mesolithic', 'Neolithic', 'Iron Age'], 'c' => 2, 'explanation' => 'Secret grading explanation']]]);
        $response = $this->get($quiz->publicUrl())->assertOk()->assertSee('multiple-choice questions and answer explanations')->assertDontSee('Secret grading explanation');
        $html = $this->withoutScripts($response->getContent());
        $this->assertStringContainsString('<h1>Prehistoric Period MCQ</h1>', $html);
        $this->assertStringContainsString('Which age used polished stone tools?', $html);
        $this->assertStringContainsString('<li>Neolithic</li>', $html);
    }

    public function test_empty_pages_are_noindexed_until_content_is_available(): void
    {
        $subject = Subject::factory()->create();
        foreach ([route('subject', $subject->slug), route('blogs.index'), route('daily'), route('weekly'), route('monthly')] as $url) {
            $this->get($url)->assertOk()->assertSee('name="robots" content="noindex,follow"', false);
        }
        Post::factory()->published()->create();
        $this->get(route('blogs.index'))->assertDontSee('name="robots" content="noindex', false);
    }

    public function test_sitemap_contains_quiz_set_pages_and_robots_announces_it(): void
    {
        foreach (['daily', 'weekly', 'monthly'] as $period) {
            $id = DB::table('practice_sets')->insertGetId(['period' => $period, 'starts_on' => '2026-10-01', 'questions' => json_encode([['q' => 'Sample question', 'o' => ['A', 'B', 'C', 'D'], 'c' => 0]]), 'created_at' => now(), 'updated_at' => now()]);
            $sitemap = $this->get(route('sitemap'))->assertOk()->assertSee(route($period.'.set', ['set' => $id]), false);
            if ($period === 'monthly') {
                $sitemap->assertDontSee('<loc>'.route($period).'</loc>', false);
            } else {
                $sitemap->assertSee('<loc>'.route($period).'</loc>', false);
            }
            $this->get(route($period))->assertDontSee('name="robots" content="noindex', false);
        }
        $this->assertStringContainsString('Sitemap: https://questionyear.in/sitemap.xml', file_get_contents(public_path('robots.txt')));
    }

    public function test_editorial_cleanup_migration_preserves_images_and_unrelated_content(): void
    {
        $subject = Subject::factory()->create(['slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create(['title' => 'Indus Valley Civilization', 'content' => '<p>It is also know as  also known as the Harappan Civilisation. modify this content</p><img src="/uploads/ruins.jpg"><table><tr><td>Original fact</td></tr></table>']);
        $other = Chapter::factory()->create(['content' => '<p>Custom unchanged content.</p>']);
        $migration = require database_path('migrations/2026_10_09_094416_clean_indus_valley_editorial_text.php');
        $migration->up();
        $this->assertStringNotContainsString('modify this content', $chapter->fresh()->content);
        $this->assertStringContainsString('<img src="/uploads/ruins.jpg">', $chapter->fresh()->content);
        $this->assertStringContainsString('<table><tr><td>Original fact</td></tr></table>', $chapter->fresh()->content);
        $this->assertSame($other->content, $other->fresh()->content);
        $cleaned = $chapter->fresh()->content;
        $migration->up();
        $this->assertSame($cleaned, $chapter->fresh()->content);
    }
}
