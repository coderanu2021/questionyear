<?php

namespace Tests\Feature;

use App\Models\Chapter;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\IndusValleyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndusValleyContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplied_document_content_is_added_to_the_existing_history_chapter_once(): void
    {
        $this->seed(CurriculumSeeder::class);
        $chapter = Chapter::where('title', 'Indus Valley Civilization')->firstOrFail();
        $count = Chapter::count();
        $this->seed(IndusValleyContentSeeder::class);
        $chapter->refresh();
        $this->assertStringContainsString('Daya Ram Sahni', $chapter->content);
        $this->assertStringContainsString('modify this content', $chapter->content);
        $this->assertSame(2, substr_count($chapter->content, '<table>'));
        $this->assertSame('Ancient History', $chapter->category);
        $this->assertSame('published', $chapter->status);
        $this->assertNotEmpty($chapter->quizzes);
        $this->seed(IndusValleyContentSeeder::class);
        $this->assertSame($count, Chapter::count());
        $this->assertSame($chapter->content, $chapter->fresh()->content);
    }

    public function test_existing_custom_content_is_preserved(): void
    {
        $this->seed(IndusValleyContentSeeder::class);
        $chapter = Chapter::where('title', 'Indus Valley Civilization')->firstOrFail();
        $chapter->update(['content' => '<p>Edited notes</p>']);
        $this->seed(IndusValleyContentSeeder::class);
        $this->assertSame('<p>Edited notes</p>', $chapter->fresh()->content);
    }
}
