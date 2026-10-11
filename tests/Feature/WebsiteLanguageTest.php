<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Subject;
use App\Models\User;
use App\WebsiteLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsiteLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_switch_translates_navigation_and_persists_between_pages(): void
    {
        Http::preventStrayRequests();
        $this->get('/')->assertOk()->assertSee('<html lang="en">', false)->assertSee('Daily MCQ');
        $this->post(route('website.language'), ['language' => 'hi', 'return_to' => '/daily-quiz'])
            ->assertRedirect('/hi/daily-quiz')->assertSessionHas('website_language', 'hi');
        $this->get('/hi/daily-quiz')->assertOk()->assertSee('<html lang="hi">', false)
            ->assertSee('दैनिक MCQ')->assertSee('विषय')->assertSee('भाषा');
        $this->get('/hi/blogs')->assertOk()->assertSee('ब्लॉग')->assertSee('<html lang="hi">', false);
        $this->post(route('website.language'), ['language' => 'en', 'return_to' => '/blogs'])->assertRedirect('/blogs');
        $this->get('/blogs')->assertOk()->assertSee('<html lang="en">', false)->assertSee('Daily MCQ');
        Http::assertNothingSent();
    }

    public function test_translation_keeps_content_and_category_values_intact(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create(['category' => 'Ancient History', 'content' => '<p>Original English chapter content.</p>']);
        $this->withSession(['website_language' => 'hi'])->get('/hi/subject/history')->assertOk()
            ->assertSee('<h1 id="sn">इतिहास</h1>', false)
            ->assertSee('<option value="Ancient History">प्राचीन इतिहास</option>', false);
        $this->get(WebsiteLocale::hindiPath(parse_url($chapter->readingUrl(0), PHP_URL_PATH)))->assertOk()->assertSee('Original English chapter content.')
            ->assertSee('होम')->assertSee('सभी विषय');
        $this->assertSame('<p>Original English chapter content.</p>', $chapter->fresh()->content);
    }

    public function test_language_validation_and_safe_return_paths(): void
    {
        $this->post(route('website.language'), ['language' => 'fr'])->assertSessionHasErrors('language');
        foreach (['https://example.com', '//example.com', '/\\example.com', "/\nexample.com"] as $returnTo) {
            $this->post(route('website.language'), ['language' => 'hi', 'return_to' => $returnTo])->assertRedirect('/hi/');
        }
        $this->post(route('website.language'), ['language' => 'hi', 'return_to' => '/blogs?page=2'])->assertRedirect('/hi/blogs?page=2');
    }

    public function test_unsupported_session_language_falls_back_and_admin_stays_in_english(): void
    {
        $this->withSession(['website_language' => 'invalid'])->get('/')->assertOk()->assertSee('<html lang="en">', false);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->withSession(['website_language' => 'hi'])
            ->get('/admin')->assertOk()->assertSee('Dashboard');
        $this->assertSame('en', app()->getLocale());
        $this->get('/hi/')->assertSee('<html lang="hi">', false);
    }

    public function test_locale_urls_have_reciprocal_hreflang_canonicals_and_local_links(): void
    {
        foreach (['/blogs', '/news', '/about', '/daily-quiz'] as $path) {
            foreach ([$path, '/hi'.$path] as $version) {
                $this->get($version.'?utm_source=test')->assertOk()
                    ->assertSee('<link rel="canonical" href="'.url($version).'">', false)
                    ->assertSee('<link rel="alternate" hreflang="en" href="'.url($path).'">', false)
                    ->assertSee('<link rel="alternate" hreflang="hi" href="'.url('/hi'.$path).'">', false);
            }
        }
        $this->withSession(['website_language' => 'hi'])->get('/')->assertOk()->assertSee('<html lang="en">', false);
        $this->get('/hi/')->assertOk()->assertSee('<html lang="hi">', false)->assertSee('href="/hi/#popular"', false);
        $this->get('/hi/admin')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/hi/about'), false);
        $this->post(route('website.language'), ['language' => 'en', 'return_to' => '/hi/blogs?page=2'])->assertRedirect('/blogs?page=2');
    }
}
