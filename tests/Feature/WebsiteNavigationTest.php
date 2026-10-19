<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function elements(string $html, string $query): \DOMNodeList
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return (new \DOMXPath($document))->query($query);
    }

    public function test_navigation_has_six_groups_matching_the_footer_without_duplicate_current_affairs(): void
    {
        Subject::factory()->create(['name' => 'Current Affairs', 'slug' => 'current-affairs']);
        $html = $this->get('/')->assertOk()->getContent();
        $summaries = $this->elements($html, '//nav[@id="nav"]/ul/li/details/summary');
        $this->assertSame(['Subjects', 'Practice', 'Exams', 'Govt Jobs', 'Current Affairs', 'Updates'], array_map(fn ($node): string => trim($node->textContent), iterator_to_array($summaries)));
        $this->assertSame(0, $this->elements($html, '//nav[@id="nav"]//a[contains(@href,"#popular")]')->length);
        $this->assertSame(0, $this->elements($html, '//nav[@id="nav"]/ul/li[1]//a[contains(@href,"current-affairs")]')->length);
        foreach ($this->elements($html, '//nav[@id="nav"]//div[contains(@class,"site-dropdown")]') as $dropdown) {
            $this->assertLessThanOrEqual(8, $dropdown->getElementsByTagName('a')->length);
        }
        $footerLinks = array_map(fn ($node): string => $node->getAttribute('href'), iterator_to_array($this->elements($html, '//footer//a')));
        foreach ($this->elements($html, '//nav[@id="nav"]//a') as $link) {
            $this->assertContains($link->getAttribute('href'), $footerLinks);
        }
        $this->assertSame(1, $this->elements($html, '//button[@id="mb" and @aria-controls="nav" and @aria-expanded="false"]')->length);
    }

    public function test_science_exam_links_require_specific_published_content(): void
    {
        $science = Subject::factory()->create(['name' => 'Physics', 'slug' => 'physics']);
        Chapter::factory()->for($science)->create();
        Post::factory()->published()->create(['title' => 'JEE future preparation', 'published_at' => now()->addDay()]);
        Post::factory()->create(['title' => 'CBSE draft preparation']);
        $html = $this->get('/')->assertOk()->getContent();
        foreach (['neet', 'jee', 'cbse-board'] as $exam) {
            $this->assertStringNotContainsString(route('exam', $exam), $html);
        }
        Post::factory()->published()->create(['title' => 'NEET preparation guide']);
        $this->app->forgetScopedInstances();
        $this->get('/')->assertOk()->assertSee(route('exam', 'neet'), false)->assertDontSee(route('exam', 'jee'), false);
    }

    public function test_exam_hub_combines_only_published_related_resources_and_keeps_old_notice_urls(): void
    {
        $subject = Subject::factory()->create(['name' => 'Mathematics', 'slug' => 'mathematics']);
        $chapter = Chapter::factory()->for($subject)->create();
        $quiz = Quiz::factory()->for($chapter)->create(['title' => 'SSC Number Practice']);
        $post = Post::factory()->published()->create(['title' => 'SSC preparation guide']);
        $unrelated = Post::factory()->published()->create(['title' => 'NEET specific preparation']);
        $draft = Post::factory()->create(['title' => 'SSC hidden draft']);
        $html = $this->get(route('exam', 'ssc'))->assertOk()->assertSee('SSC preparation hub')->assertSee('Exam-specific MCQs and mock tests')->assertSee($quiz->publicUrl(), false)->assertSee(route('blogs.show', $post->slug), false)->assertDontSee($draft->title)->getContent();
        $main = $this->elements($html, '//main')->item(0)->textContent;
        $this->assertStringNotContainsString($unrelated->title, $main);
        $this->assertSame(1, $this->elements($html, '//h1')->length);
        $this->assertSame(1, $this->elements($html, '//main//a[@href="'.route('exams.index').'"]')->length);
        $this->get(route('notifications.show', 'ssc-selection-posts-phase-xiv-2026'))->assertOk();
        $this->get('/exams/railway')->assertStatus(301)->assertRedirect(route('exam', 'railways'));
        $this->get('/exams/baking')->assertStatus(301)->assertRedirect(route('exam', 'banking'));
        $this->get('/exams/unknown')->assertNotFound();
    }

    public function test_topic_practice_can_be_filtered_without_changing_quiz_urls(): void
    {
        $maths = Subject::factory()->create(['name' => 'Mathematics', 'slug' => 'mathematics']);
        $history = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        $first = Quiz::factory()->for(Chapter::factory()->for($maths))->create(['title' => 'Arithmetic revision']);
        $second = Quiz::factory()->for(Chapter::factory()->for($history))->create(['title' => 'Empire revision']);
        $this->get(route('practice.index', ['subject' => 'mathematics']))->assertOk()->assertSee($first->publicUrl(), false)->assertDontSee($second->publicUrl(), false);
        $this->get(route('practice.index', ['mode' => 'mock']))->assertOk()->assertSee('Create a mock test in My learning');
        $this->get($first->publicUrl())->assertOk();
        $this->get('/daily-quiz')->assertOk();
        $this->get('/weekly-quiz')->assertOk();
        $this->get('/monthly-quiz')->assertOk();
    }

    public function test_current_affairs_capsules_use_calendar_dates_and_keep_undated_archive_sets(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(12, 0));
        $subject = Subject::factory()->create(['name' => 'Current Affairs', 'slug' => 'current-affairs']);
        $today = Quiz::factory()->create(['chapter_id' => null, 'subject_id' => $subject->id, 'title' => 'Today CA', 'status' => 'published', 'quiz_date' => '2026-10-21']);
        Quiz::factory()->create(['chapter_id' => null, 'subject_id' => $subject->id, 'title' => 'Monday CA', 'status' => 'published', 'quiz_date' => '2026-10-19']);
        Quiz::factory()->create(['chapter_id' => null, 'subject_id' => $subject->id, 'title' => 'Earlier CA', 'status' => 'published', 'quiz_date' => '2026-10-10']);
        Quiz::factory()->create(['chapter_id' => null, 'subject_id' => $subject->id, 'title' => 'Undated CA', 'status' => 'published', 'quiz_date' => null]);
        $this->get(route('current-affairs', ['period' => 'daily']))->assertOk()->assertSee($today->publicUrl(), false)->assertDontSee('Monday CA')->assertDontSee('Undated CA');
        $this->get(route('current-affairs', ['period' => 'weekly']))->assertOk()->assertSee('Monday CA')->assertDontSee('Earlier CA');
        $this->get(route('current-affairs', ['period' => 'monthly']))->assertOk()->assertSee('Earlier CA')->assertDontSee('Undated CA');
        $this->get(route('current-affairs'))->assertOk()->assertSee('Undated CA');
        $this->get('/current-affairs?period=invalid')->assertSessionHasErrors('period');
    }

    public function test_sitemap_includes_ready_hubs_and_excludes_empty_science_hubs(): void
    {
        $this->get(route('sitemap'))->assertOk()->assertSee(route('exam', 'ssc'), false)->assertSee(route('exams.index'), false)->assertDontSee(route('exam', 'neet'), false);
        $this->get('/subjects/science')->assertOk()->assertSee('No published resources in this section yet.');
        $this->get('/subjects/unknown')->assertNotFound();
    }
}
