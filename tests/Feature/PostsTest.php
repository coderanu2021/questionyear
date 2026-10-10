<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_form_saves_and_renders_metadata_using_existing_page_seo(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $this->get(route('admin.posts.create'))->assertOk()->assertSee('name="meta_title"', false)->assertSee('name="meta_description"', false);
        $data = ['title' => 'Study tips', 'type' => 'blog', 'status' => 'published', 'content' => '<p>Practice daily.</p>', 'meta_title' => 'Custom SEO title', 'meta_description' => 'Custom SEO description'];
        $this->post(route('admin.posts.store'), $data)->assertRedirect();
        $post = Post::firstOrFail();
        $this->assertDatabaseHas('page_seo', ['page_key' => 'post:'.$post->id, 'meta_title' => $data['meta_title'], 'meta_description' => $data['meta_description']]);
        $this->get(route('blogs.show', $post->slug))->assertOk()->assertSee('<title>Custom SEO title</title>', false)->assertSee('content="Custom SEO description"', false);
        $this->get(route('admin.posts.edit', $post))->assertOk()->assertSee('Custom SEO title')->assertSee('Custom SEO description');
        DB::table('page_seo')->where('page_key', 'post:'.$post->id)->update(['meta_keywords' => 'study']);
        $this->put(route('admin.posts.update', $post), [...$data, 'meta_title' => '', 'meta_description' => ''])->assertRedirect();
        $this->assertDatabaseHas('page_seo', ['page_key' => 'post:'.$post->id, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => 'study']);
        $this->put(route('admin.posts.update', $post), [...$data, 'meta_title' => str_repeat('a', 256)])->assertSessionHasErrors('meta_title');
        $this->put(route('admin.posts.update', $post), [...$data, 'meta_description' => str_repeat('a', 1001)])->assertSessionHasErrors('meta_description');
    }

    public function test_editor_images_can_be_uploaded_and_served(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $response = $this->postJson(route('admin.posts.images'), ['upload' => UploadedFile::fake()->image('photo.png')])->assertOk();
        $this->get($response->json('url'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertCount(1, Storage::disk('local')->files('post-images'));
        $this->postJson(route('admin.posts.images'), ['upload' => UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml')])->assertUnprocessable();
        $this->postJson(route('admin.posts.images'), ['upload' => UploadedFile::fake()->image('large.png')->size(5121)])->assertUnprocessable();
        foreach ([['role' => 'student', 'status' => 'active'], ['role' => 'admin', 'status' => 'blocked']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes))->postJson(route('admin.posts.images'), [])->assertForbidden();
        }
        $this->get('/post-images/missing.png')->assertNotFound();
    }

    public function test_blog_renders_formatted_content_and_removes_unsafe_html(): void
    {
        $post = Post::factory()->published()->create(['content' => '<p><strong>Study</strong></p><figure><img src="/post-images/photo.png" onerror="alert(1)"><figcaption>Photo</figcaption></figure><script>alert(2)</script><a href="javascript:alert(3)">Link</a>']);
        $this->get(route('blogs.show', $post->slug))->assertOk()
            ->assertSee('<strong>Study</strong>', false)->assertSee('<img src="/post-images/photo.png">', false)
            ->assertDontSee('onerror', false)->assertDontSee('javascript:', false)->assertDontSee('alert(2)', false);
    }

    public function test_homepage_shows_the_latest_eight_published_blogs(): void
    {
        $blogs = Post::factory()->published()->count(10)->create();

        $response = $this->get(route('home'))->assertOk()->assertSee('Latest blogs')->assertSee('home-blog-grid');
        $response->assertViewHas('latestBlogs', fn ($latestBlogs): bool => $latestBlogs->pluck('id')->all() === $blogs->reverse()->take(8)->pluck('id')->all());
        foreach ($blogs->reverse()->take(8) as $blog) {
            $response->assertSee($blog->title);
        }
        foreach ($blogs->take(2) as $blog) {
            $response->assertDontSee($blog->title);
        }
    }

    public function test_frontend_only_shows_published_blogs(): void
    {
        $blog = Post::factory()->published()->create(['content' => '<script>alert(1)</script>']);
        $draft = Post::factory()->create();
        $news = Post::factory()->published()->create(['type' => 'news']);
        $future = Post::factory()->published()->create(['published_at' => now()->addDay()]);

        foreach (['/', '/blogs'] as $url) {
            $this->get($url)->assertOk()->assertSee($blog->title)->assertDontSee($draft->title)->assertDontSee($news->title)->assertDontSee($future->title);
        }
        $this->get(route('blogs.show', $blog->slug))->assertOk()->assertSee($blog->content)->assertDontSee($blog->content, false);
        foreach ([$draft, $news, $future] as $hidden) {
            $this->get(route('blogs.show', $hidden->slug))->assertNotFound();
        }
        $this->get('/news')->assertNotFound();
        $this->get('/news/'.$news->slug)->assertNotFound();
    }

    public function test_active_admin_can_manage_blogs_and_news(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $this->get(route('admin.posts.index'))->assertOk();
        $this->get(route('admin.posts.create'))->assertOk();
        $data = ['title' => 'Exam preparation tips', 'type' => 'blog', 'status' => 'draft', 'content' => 'Practice consistently.', 'excerpt' => 'Study tips.'];
        $this->post(route('admin.posts.store'), $data)->assertRedirect(route('admin.posts.index'));
        $post = Post::firstOrFail();
        $this->assertSame('exam-preparation-tips', $post->slug);
        $this->assertNull($post->published_at);
        $this->get(route('admin.posts.edit', $post))->assertOk()->assertSee($post->title);
        $this->put(route('admin.posts.update', $post), [...$data, 'status' => 'published'])->assertRedirect();
        $this->assertNotNull($post->fresh()->published_at);
        $this->get(route('blogs.show', $post->slug))->assertOk();
        $this->put(route('admin.posts.update', $post), [...$data, 'status' => 'published', 'type' => 'news'])->assertRedirect();
        $this->get(route('blogs.show', $post->slug))->assertNotFound();
        $this->get(route('admin.posts.index', ['type' => 'news']))->assertOk()->assertSee($post->title);
        $this->get(route('admin.posts.index', ['type' => 'blog']))->assertOk()->assertDontSee($post->title);
        $this->put(route('admin.posts.update', $post), $data)->assertRedirect();
        $this->assertNull($post->fresh()->published_at);
        $this->delete(route('admin.posts.destroy', $post))->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_management_requires_an_active_admin(): void
    {
        $post = Post::factory()->create();
        $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
        foreach ([['role' => 'student', 'status' => 'active'], ['role' => 'admin', 'status' => 'blocked']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->get(route('admin.posts.index'))->assertForbidden();
            $this->get(route('admin.posts.create'))->assertForbidden();
            $this->get(route('admin.posts.edit', $post))->assertForbidden();
            $this->post(route('admin.posts.store'), [])->assertForbidden();
            $this->put(route('admin.posts.update', $post), [])->assertForbidden();
            $this->delete(route('admin.posts.destroy', $post))->assertForbidden();
        }
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_post_validation_and_duplicate_slugs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $post = Post::factory()->create();
        $this->post(route('admin.posts.store'), [])->assertSessionHasErrors(['title']);
        $this->post(route('admin.posts.store'), ['title' => ['Invalid'], 'slug' => ['Invalid']])->assertSessionHasErrors(['title', 'slug']);
        $this->post(route('admin.posts.store'), ['title' => 'Duplicate', 'slug' => $post->slug, 'type' => 'invalid', 'status' => 'invalid', 'content' => 'Content'])->assertSessionHasErrors(['slug', 'type', 'status']);
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_empty_blog_listing_and_pagination(): void
    {
        $this->get(route('blogs.index'))->assertOk()->assertSee('New blogs are on the way');
        Post::factory()->published()->count(10)->create();
        $this->get(route('blogs.index'))->assertOk()->assertSee('Next');
        $this->get(route('blogs.index', ['page' => 2]))->assertOk();
    }
}
