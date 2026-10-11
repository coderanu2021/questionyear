<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_stays_in_english_without_a_language_selector(): void
    {
        $this->withSession(['website_language' => 'hi'])->get('/')->assertOk()
            ->assertSee('<html lang="en">', false)->assertSee('Daily MCQ')
            ->assertDontSee('website-language')->assertDontSee('hreflang=', false);
        $this->get('/blogs')->assertOk()->assertSee('<html lang="en">', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/hi/', false);
    }

    public function test_hindi_and_alternate_english_routes_are_removed(): void
    {
        foreach (['/hi', '/hi/blogs', '/hi/news', '/en'] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->post('/language', ['language' => 'hi'])->assertNotFound();
    }
}
