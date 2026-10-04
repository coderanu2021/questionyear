<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_seo_is_saved_edited_and_rendered_in_the_page_head(): void
    {
        $chapter = Chapter::factory()->create();
        Quiz::factory()->for($chapter)->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $state = $this->actingAs($admin)->get(route('admin'))->viewData('state');
        $state['chapters'][0]['meta_title'] = 'Study & practice "Maths"';
        $state['chapters'][0]['meta_description'] = 'Learn <topics> with chapter practice.';
        $state['chapters'][0]['meta_keywords'] = 'maths, learning, practice';
        $saved = $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonPath('chapters.0.meta_title', $state['chapters'][0]['meta_title'])->json();
        $this->assertDatabaseHas('chapters', ['id' => $chapter->id, 'meta_keywords' => 'maths, learning, practice']);
        foreach (['learn', 'quiz'] as $route) {
            $this->get(route($route, ['subject' => $chapter->subject->slug, 'chapter' => 0]))->assertOk()->assertSee('<title>Study &amp; practice &quot;Maths&quot;</title>', false)->assertSee('content="Learn &lt;topics&gt; with chapter practice."', false)->assertSee('name="keywords" content="maths, learning, practice"', false);
        }
        $saved['chapters'][0]['meta_title'] = 'New SEO title';
        $saved['chapters'][0]['meta_description'] = '';
        $saved['chapters'][0]['meta_keywords'] = '';
        $this->putJson(route('admin.save'), $saved)->assertOk();
        $this->assertNull($chapter->fresh()->meta_description);
        $this->assertNull($chapter->fresh()->meta_keywords);
        $this->get(route('learn', ['subject' => $chapter->subject->slug, 'chapter' => 0]))->assertSee('<title>New SEO title</title>', false);
    }

    public function test_new_chapter_seo_is_validated_and_empty_titles_have_a_fallback(): void
    {
        $chapter = Chapter::factory()->create();
        $state = $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin'))->viewData('state');
        $state['chapters'][] = ['id' => $chapter->id + 1, 'title' => 'Fresh chapter', 'subject' => $chapter->subject->name, 'lessons' => 1, 'status' => 'published', 'content' => '<p>New notes</p>', 'meta_title' => 'Fresh SEO', 'meta_description' => 'New description', 'meta_keywords' => 'fresh'];
        $saved = $this->putJson(route('admin.save'), $state)->assertOk()->json();
        $this->assertDatabaseHas('chapters', ['title' => 'Fresh chapter', 'meta_title' => 'Fresh SEO']);
        $saved['chapters'][0]['meta_title'] = str_repeat('a', 256);
        $this->putJson(route('admin.save'), $saved)->assertUnprocessable()->assertJsonValidationErrors('chapters.0.meta_title');
        $this->get(route('learn', ['subject' => $chapter->subject->slug, 'chapter' => 0]))->assertOk()->assertSee('<title>'.e($chapter->title).' – questionyear</title>', false);
    }
}
