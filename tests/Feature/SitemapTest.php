<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_tracks_public_content_and_excludes_unavailable_pages(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        Chapter::factory()->for($subject)->create(['status' => 'draft']);
        $chapter = Chapter::factory()->for($subject)->create(['category' => 'Ancient History', 'title' => 'Early kingdoms']);
        $quiz = Quiz::factory()->for($chapter)->create();
        $hiddenQuiz = Quiz::factory()->create(['chapter_id' => Chapter::factory()->create(['status' => 'draft'])->id]);
        $affairs = Subject::factory()->create(['name' => 'Current Affairs', 'slug' => 'current-affairs']);
        $affairsChapter = Chapter::factory()->for($affairs)->create();
        $affairsQuiz = Quiz::factory()->for($affairsChapter)->create();
        $blog = Post::factory()->published()->create();
        $draft = Post::factory()->create();
        $future = Post::factory()->published()->create(['published_at' => now()->addDay()]);
        $news = Post::factory()->published()->create(['type' => 'news']);

        $response = $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $urls = array_map(fn ($entry) => (string) $entry->loc, iterator_to_array($xml->url, false));
        foreach ([route('home'), route('subject', $subject->slug), $chapter->readingUrl(0), $quiz->publicUrl(), $affairsQuiz->publicUrl(), route('blogs.show', $blog->slug)] as $url) {
            $this->assertContains($url, $urls);
        }
        foreach ([$hiddenQuiz->publicUrl(), $affairsChapter->readingUrl(0), route('blogs.show', $draft->slug), route('blogs.show', $future->slug), route('blogs.show', $news->slug), route('login'), route('learning')] as $url) {
            $this->assertNotContains($url, $urls);
        }
        $this->get($chapter->readingUrl(0))->assertOk();
        $blog->update(['status' => 'draft']);
        $this->get(route('sitemap'))->assertDontSee(route('blogs.show', $blog->slug), false);
        $added = Post::factory()->published()->create();
        $this->get(route('sitemap'))->assertSee(route('blogs.show', $added->slug), false);
    }
}
