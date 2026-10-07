<?php

namespace Tests\Feature;

use App\Models\Chapter;
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
}
