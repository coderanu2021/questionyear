<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\JobNotification;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_have_one_canonical_without_tracking_parameters(): void
    {
        foreach (['/', '/about', '/daily-quiz', '/weekly-quiz', '/monthly-quiz', '/upcoming', '/weekly-leaderboard'] as $path) {
            $response = $this->get($path.'?utm_source=google')->assertOk();
            $response->assertSee('<link rel="canonical" href="'.url($path).'">', false);
            $this->assertSame(1, substr_count($response->getContent(), 'rel="canonical"'));
        }
    }

    public function test_configured_public_origin_consolidates_http_and_www_variants(): void
    {
        config(['app.url' => 'https://questionyear.in']);
        $this->get('http://www.questionyear.in/about?ref=duplicate')->assertOk()
            ->assertSee('<link rel="canonical" href="https://questionyear.in/about">', false);
    }

    public function test_chapters_keep_their_own_canonical_and_auth_pages_are_noindex(): void
    {
        $chapter = Chapter::factory()->create();
        $url = route('learn', ['subject' => $chapter->subject->slug, 'chapter' => 0]);
        $this->get($url.'?utm_source=google')->assertOk()->assertSee('<link rel="canonical" href="'.$url.'">', false);
        foreach (['/login', '/register'] as $path) {
            $this->get($path)->assertOk()->assertSee('<meta name="robots" content="noindex,follow">', false);
        }
    }

    public function test_legacy_plural_subject_urls_redirect_to_the_singular_canonical_url(): void
    {
        $subject = Subject::factory()->create(['slug' => 'custom-subject']);

        $this->get('/subjects/'.$subject->slug)->assertRedirect(route('subject', $subject->slug))->assertStatus(301);
        $this->get('/subject/'.$subject->slug)->assertOk()->assertSee('<link rel="canonical" href="'.route('subject', $subject->slug).'">', false);
        $this->get('/subjects/science')->assertOk();
    }

    public function test_current_affairs_period_query_urls_redirect_to_clean_canonical_routes(): void
    {
        $cleanUrl = route('current-affairs.period', ['period' => 'daily']);

        $this->get('/current-affairs?period=daily')->assertRedirect($cleanUrl)->assertStatus(301);
        $this->get($cleanUrl)->assertOk()->assertSee('<link rel="canonical" href="'.$cleanUrl.'">', false);
    }

    public function test_public_page_types_render_canonical_open_graph_and_twitter_metadata(): void
    {
        $subject = Subject::factory()->create(['slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create(['status' => 'published']);
        $quiz = Quiz::factory()->for($chapter)->create();
        $blog = Post::factory()->published()->create(['type' => 'blog']);
        $news = Post::factory()->published()->create(['type' => 'news']);
        $job = JobNotification::factory()->create(['status' => 'published', 'official_url' => 'https://example.gov.in/notice']);
        $pages = [
            route('home') => 'website',
            route('subject', $subject->slug) => 'website',
            $quiz->publicUrl() => 'website',
            route('blogs.show', $blog->slug) => 'article',
            route('news.show', $news->slug) => 'article',
            route('jobs.index') => 'website',
            route('job-notices.show', ['year' => $job->created_at->format('Y'), 'slug' => $job->slug]) => 'website',
            route('exam', 'upsc') => 'website',
        ];

        foreach ($pages as $url => $type) {
            $response = $this->get($url)->assertOk();
            $response->assertSee('<link rel="canonical" href="'.$url.'">', false)
                ->assertSee('<meta property="og:url" content="'.$url.'">', false)
                ->assertSee('<meta property="og:type" content="'.$type.'">', false)
                ->assertSee('<meta name="twitter:url" content="'.$url.'">', false)
                ->assertSee('<meta name="twitter:card" content="summary">', false)
                ->assertSee('<meta name="twitter:title"', false)
                ->assertSee('<meta name="twitter:description"', false);
        }
    }
}
