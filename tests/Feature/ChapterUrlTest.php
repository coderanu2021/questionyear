<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_chapter_urls_use_the_category_and_title(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        foreach (['Ancient History', 'Medieval History', 'Modern History'] as $index => $category) {
            $chapter = Chapter::factory()->for($subject)->create(['title' => 'Prehistoric Period', 'category' => $category]);
            $url = $chapter->readingUrl($index);
            $response = $this->get($url)->assertOk()->assertSee('Prehistoric Period');
            $this->assertSame($url, $response->viewData('curriculum')['chapterUrls']['history:'.$index]);
            $this->get(route('learn', ['subject' => 'history', 'chapter' => $index]))->assertStatus(301)->assertRedirect($url);
        }
        $this->assertSame(url('/ancient-history/prehistoric-period'), Chapter::first()->readingUrl(0));
    }

    public function test_history_urls_do_not_expose_drafts_or_other_categories(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        Chapter::factory()->for($subject)->create(['title' => 'Prehistoric Period', 'category' => 'Ancient History']);
        Chapter::factory()->for($subject)->create(['title' => 'Private Chapter', 'category' => 'Ancient History', 'status' => 'draft']);
        $this->get('/modern-history/prehistoric-period')->assertNotFound();
        $this->get('/ancient-history/private-chapter')->assertNotFound();
        $this->get('/ancient-history/missing-chapter')->assertNotFound();
    }

    public function test_existing_prehistoric_chapter_receives_a_category_without_overwriting_admin_choices(): void
    {
        $subject = Subject::factory()->create(['name' => 'History', 'slug' => 'history']);
        $chapter = Chapter::factory()->for($subject)->create(['title' => 'Prehistoric Period', 'category' => null]);
        $assigned = Chapter::factory()->for($subject)->create(['title' => 'Freedom Struggle', 'category' => 'Ancient History']);
        $migration = require database_path('migrations/2026_10_05_154302_assign_categories_to_existing_history_chapters.php');
        $migration->up();
        $this->assertSame('Ancient History', $chapter->fresh()->category);
        $this->assertSame('Ancient History', $assigned->fresh()->category);
        $this->get('/learn/history/0')->assertStatus(301)->assertRedirect(url('/ancient-history/prehistoric-period'));
        $this->get('/ancient-history/prehistoric-period')->assertOk();
    }
}
