<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_save_and_clear_metadata_without_changing_page_content(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $this->get(route('admin.seo.index'))->assertOk()->assertSee('value="route:home"', false)->assertSee('name="meta_keywords"', false);
        $this->saveSeo('route:home', 'Custom & "Homepage"');
        $response = $this->get(route('home'))->assertOk()
            ->assertSee('<title>Custom &amp; &quot;Homepage&quot;</title>', false)
            ->assertSee('name="description" content="Description &lt;safe&gt;"', false)
            ->assertSee('name="keywords" content="study, practice"', false)
            ->assertSee('Practice any subject. Know where you stand.');
        $this->assertSame(1, preg_match_all('/<title>/', $response->getContent()));
        foreach (['description', 'keywords'] as $name) {
            $this->assertSame(1, preg_match_all('/<meta name="'.$name.'"/', $response->getContent()));
        }
        $this->get(route('admin.seo.index', ['page_key' => 'route:home']))->assertOk()->assertSee('Custom &amp; &quot;Homepage&quot;', false);
        $this->put(route('admin.seo.update'), ['page_key' => 'route:home', 'meta_title' => '', 'meta_description' => '', 'meta_keywords' => ''])->assertRedirect();
        $this->get(route('home'))->assertOk()->assertSee('<title>Free GK Quizzes &amp; Chapter Notes | questionyear</title>', false)->assertDontSee('Description &lt;safe&gt;', false);
    }

    public function test_static_practice_auth_and_error_pages_render_saved_metadata(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);
        $pages = [
            'page:about' => '/about', 'page:contact' => '/contact', 'page:help' => '/help',
            'page:careers' => '/careers', 'page:privacy' => '/privacy', 'page:terms' => '/terms',
            'page:cookies' => '/cookies', 'page:leaderboard' => '/leaderboard', 'page:forgot-password' => '/forgot-password',
            'route:daily' => '/daily-quiz', 'route:weekly' => '/weekly-quiz', 'route:monthly' => '/monthly-quiz',
            'route:blogs.index' => '/blogs', 'route:upcoming' => '/upcoming', 'route:feedback' => '/feedback',
            'route:progress' => '/progress', 'route:learning' => '/my-learning',
            'route:learning.leaderboard' => '/weekly-leaderboard', 'route:learning.reports' => '/admin/question-reports',
            'route:password.reset' => '/reset-password/test-token', 'exam:neet' => '/exams/neet',
            'exam:upsc' => '/upcoming?exam=upsc', 'admin:settings' => '/admin/settings',
            'admin:dashboard' => '/admin', 'admin:posts' => '/admin/posts',
        ];
        foreach ($pages as $key => $url) {
            $this->saveSeo($key, 'SEO '.$key);
            $this->get($url)->assertOk()->assertSee('<title>SEO '.$key.'</title>', false)
                ->assertSee('name="description" content="Description &lt;safe&gt;"', false)
                ->assertSee('name="keywords" content="study, practice"', false);
        }
        $this->saveSeo('error:404', 'Custom missing page');
        $this->get('/missing-page')->assertNotFound()->assertSee('<title>Custom missing page</title>', false)->assertSee('name="robots" content="noindex"', false);
        foreach (['login', 'register'] as $route) {
            $this->actingAs($admin);
            $this->saveSeo('route:'.$route, 'SEO '.$route);
            $this->post(route('logout'));
            $this->get(route($route))->assertOk()->assertSee('<title>SEO '.$route.'</title>', false)
                ->assertSee('window.PAGE_SEO_TITLE=', false)->assertSee('name="robots" content="noindex,follow"', false);
        }
    }

    public function test_dynamic_pages_have_independent_metadata_and_type_fallbacks(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $subject = Subject::factory()->create();
        $chapter = Chapter::factory()->for($subject)->create(['meta_title' => 'Existing chapter SEO']);
        $quiz = Quiz::factory()->for($chapter)->create();
        $blog = Post::factory()->published()->create();
        $otherBlog = Post::factory()->published()->create();
        $this->get(route('admin.seo.index'))->assertOk()->assertSee('value="subject:'.$subject->id.'"', false)
            ->assertSee('value="chapter:'.$chapter->id.'"', false)->assertSee('value="quiz:'.$quiz->id.'"', false)
            ->assertSee('value="post:'.$blog->id.'"', false);
        $this->get($chapter->readingUrl(0))->assertSee('<title>Existing chapter SEO</title>', false);
        $this->saveSeo('route:blogs.show', 'All blog pages');
        $this->saveSeo('post:'.$blog->id, 'Specific blog');
        $this->saveSeo('subject:'.$subject->id, 'Specific subject');
        $this->saveSeo('chapter:'.$chapter->id, 'Specific chapter');
        $this->saveSeo('quiz:'.$quiz->id, 'Specific quiz');
        foreach ([route('blogs.show', $blog->slug) => 'Specific blog', route('blogs.show', $otherBlog->slug) => 'All blog pages', route('subject', $subject->slug) => 'Specific subject', $chapter->readingUrl(0) => 'Specific chapter', $quiz->publicUrl() => 'Specific quiz'] as $url => $title) {
            $this->get($url)->assertOk()->assertSee('<title>'.$title.'</title>', false);
        }
        $blog->update(['slug' => 'renamed-blog']);
        $quiz->update(['title' => 'Renamed quiz']);
        $this->get(route('blogs.show', $blog->slug))->assertOk()->assertSee('<title>Specific blog</title>', false);
        $this->get($quiz->publicUrl())->assertOk()->assertSee('<title>Specific quiz</title>', false);
    }

    public function test_website_defaults_can_be_overridden_field_by_field(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $this->saveSeo('defaults', 'Default SEO');
        $this->put(route('admin.seo.update'), ['page_key' => 'page:about', 'meta_title' => 'About SEO', 'meta_description' => '', 'meta_keywords' => 'about'])->assertRedirect();
        $this->get('/about')->assertOk()->assertSee('<title>About SEO</title>', false)
            ->assertSee('name="description" content="Description &lt;safe&gt;"', false)->assertSee('name="keywords" content="about"', false);
        $this->get('/contact')->assertOk()->assertSee('<title>Default SEO</title>', false);
    }

    public function test_history_chapter_metadata_survives_a_changed_title_and_canonical_url(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create(['title' => 'Early India', 'category' => 'Ancient History']);
        $this->saveSeo('chapter:'.$chapter->id, 'Custom history SEO');
        $this->get($chapter->readingUrl(0))->assertOk()->assertSee('<title>Custom history SEO</title>', false);
        $chapter->update(['title' => 'Ancient India']);
        $this->get($chapter->readingUrl(0))->assertOk()->assertSee('<title>Custom history SEO</title>', false);
        $this->get(route('learn', ['subject' => 'history', 'chapter' => 0]))->assertRedirect($chapter->readingUrl(0));
    }

    public function test_only_active_admins_can_manage_seo(): void
    {
        $this->get(route('admin.seo.index'))->assertRedirect(route('login'));
        $this->putJson(route('admin.seo.update'), [])->assertUnauthorized();
        foreach ([['role' => 'student', 'status' => 'active'], ['role' => 'admin', 'status' => 'inactive'], ['role' => 'admin', 'status' => 'blocked']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->get(route('admin.seo.index'))->assertForbidden();
            $this->putJson(route('admin.seo.update'), ['page_key' => 'route:home', 'meta_title' => 'Unauthorized'])->assertForbidden();
        }
        $this->assertDatabaseCount('page_seo', 0);
    }

    public function test_invalid_page_keys_and_metadata_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $this->putJson(route('admin.seo.update'), ['page_key' => 'arbitrary-page', 'meta_title' => 'Invalid'])->assertUnprocessable()->assertJsonValidationErrors('page_key');
        $this->putJson(route('admin.seo.update'), ['page_key' => 'route:home', 'meta_title' => str_repeat('a', 256), 'meta_description' => str_repeat('a', 1001), 'meta_keywords' => ['invalid']])->assertUnprocessable()->assertJsonValidationErrors(['meta_title', 'meta_description', 'meta_keywords']);
        $this->getJson(route('admin.seo.index', ['page_key' => 'invalid']))->assertUnprocessable();
        $this->assertDatabaseCount('page_seo', 0);
    }

    private function saveSeo(string $key, string $title): void
    {
        $this->put(route('admin.seo.update'), ['page_key' => $key, 'meta_title' => $title, 'meta_description' => 'Description <safe>', 'meta_keywords' => 'study, practice'])
            ->assertRedirect(route('admin.seo.index', ['page_key' => $key]));
    }
}
