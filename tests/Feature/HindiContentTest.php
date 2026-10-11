<?php

namespace Tests\Feature;

use App\HindiContent;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HindiContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_hindi_translation_changes_display_text_without_changing_links_values_or_scripts(): void
    {
        $html = HindiContent::translateHtml('<!DOCTYPE html><html><head><meta name="description" content="History"></head><body><a href="/subject/history">History</a><input name="category" value="History" placeholder="History"><script>const subject="History";</script><p><strong>History</strong></p></body></html>');
        $this->assertStringContainsString('href="/subject/history">इतिहास</a>', $html);
        $this->assertStringContainsString('value="History"', $html);
        $this->assertStringContainsString('placeholder="इतिहास"', $html);
        $this->assertStringContainsString('const subject="History";', $html);
        $this->assertStringContainsString('<strong>इतिहास</strong>', $html);
        $this->assertStringContainsString('content="इतिहास"', $html);
    }

    public function test_homepage_defaults_to_hindi_and_english_original_posts_remain_intact(): void
    {
        Http::preventStrayRequests();
        $post = Post::factory()->published()->create(['title' => 'History', 'content' => '<p>History</p>']);
        $this->get('/')->assertOk()->assertSee('<html lang="hi">', false)->assertSee('इतिहास')
            ->assertSee('hreflang="en" href="'.url('/en/').'"', false);
        $this->get('/hi/blogs/'.$post->slug)->assertOk()->assertSee('<p>इतिहास</p>', false);
        $this->get('/blogs/'.$post->slug)->assertOk()->assertSee('<p>History</p>', false);
        $this->get('/en/')->assertOk()->assertSee('<html lang="en">', false);
        $this->assertSame('<p>History</p>', $post->fresh()->content);
        Http::assertNothingSent();
    }
}
