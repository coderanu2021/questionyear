<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\HistoryStudyGuideSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class HistoryStudyGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_history_chapters_have_readable_guides_and_explained_mcqs(): void
    {
        $this->seed(CurriculumSeeder::class);
        $this->seed(HistoryStudyGuideSeeder::class);
        $chapters = Chapter::whereHas('subject', fn ($query) => $query->where('slug', 'history'))->get();
        $this->assertCount(9, $chapters);
        foreach ($chapters as $chapter) {
            $this->assertGreaterThanOrEqual(7, count($chapter->study_guide['s']));
            $this->assertNotEmpty($chapter->study_guide['sum']);
            $quiz = $chapter->quizzes()->where('title', $chapter->title.' — Explained Practice MCQs')->firstOrFail();
            $this->assertCount(10, $quiz->questions);
            foreach ($quiz->questions as $question) {
                $this->assertCount(4, array_unique($question['o']));
                $this->assertArrayHasKey($question['c'], $question['o']);
                $this->assertNotEmpty($question['explanation']);
            }
            $url = route('chapter', ['category' => Str::slug($chapter->category), 'chapterSlug' => Str::slug($chapter->title)]);
            $this->get($url)->assertOk()->assertSee($chapter->study_guide['s'][0]['h'])->assertSee($quiz->publicUrl(), false);
            $this->get($quiz->publicUrl())->assertOk();
        }
    }

    public function test_import_preserves_original_content_notes_quizzes_and_user_results(): void
    {
        $subject = Subject::factory()->create(['slug' => 'history', 'name' => 'History']);
        $notes = ['s' => [['h' => 'Original notes', 'p' => ['Keep these notes.']]], 'sum' => ['Existing summary']];
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'title' => 'Vedic Age', 'content' => '<p>My original lesson</p>', 'notes' => $notes, 'status' => 'draft', 'category' => 'Ancient History', 'meta_title' => 'Custom SEO title']);
        $quiz = Quiz::factory()->create(['chapter_id' => $chapter->id]);
        $user = User::factory()->create();
        $attempt = $quiz->attempts()->create(['user_id' => $user->id, 'answers' => [3], 'score' => 1, 'total' => 1, 'percentage' => 100, 'seconds' => 30]);
        $originalChapter = $chapter->fresh()->getAttributes();
        $originalQuiz = $quiz->fresh()->getAttributes();
        $this->seed(HistoryStudyGuideSeeder::class);
        $chapter->refresh();
        foreach ($originalChapter as $key => $value) {
            if (! in_array($key, ['study_guide', 'updated_at'], true)) {
                $this->assertSame($value, $chapter->getRawOriginal($key), $key);
            }
        }
        $this->assertSame($originalQuiz, $quiz->fresh()->getAttributes());
        $this->assertNotNull($attempt->fresh());
        $this->assertNotNull($user->fresh());
    }

    public function test_repeated_import_keeps_edits_and_does_not_duplicate_content(): void
    {
        $this->seed(HistoryStudyGuideSeeder::class);
        $chapter = Chapter::where('title', 'Vedic Age')->firstOrFail();
        $guide = $chapter->study_guide;
        $guide['s'][0]['p'] = ['My edited guide'];
        $chapter->update(['study_guide' => $guide]);
        $quiz = $chapter->quizzes()->firstOrFail();
        $questions = $quiz->questions;
        $questions[0]['explanation'] = 'My edited explanation';
        $quiz->update(['questions' => $questions]);
        $chapterCount = Chapter::count();
        $quizCount = Quiz::count();
        $this->seed(HistoryStudyGuideSeeder::class);
        $this->assertSame($chapterCount, Chapter::count());
        $this->assertSame($quizCount, Quiz::count());
        $this->assertSame($guide, $chapter->fresh()->study_guide);
        $this->assertSame($questions, $quiz->fresh()->questions);
    }

    public function test_original_notes_and_new_explanations_are_both_shown(): void
    {
        $subject = Subject::factory()->create(['slug' => 'history', 'name' => 'History']);
        Chapter::factory()->create(['subject_id' => $subject->id, 'title' => 'Vedic Age', 'content' => null, 'notes' => ['s' => [['h' => 'Original notes', 'p' => ['Keep these notes.']]], 'sum' => ['Existing summary']]]);
        $this->seed(HistoryStudyGuideSeeder::class);
        $this->get('/ancient-history/vedic-age')->assertOk()->assertSee('Keep these notes.')->assertSee('Friendly guide: what does Vedic mean?')->assertSee('Existing summary');
    }

    public function test_deployment_migration_adds_guides_and_rollback_retains_them(): void
    {
        $this->seed(CurriculumSeeder::class);
        $chapter = Chapter::where('title', 'Vedic Age')->firstOrFail();
        $chapter->update(['content' => '<p>Live server content</p>']);
        $migration = require database_path('migrations/2026_10_14_050327_add_history_study_guides_to_chapters.php');
        $migration->up();
        $migration->up();
        $migration->down();
        $this->assertTrue(Schema::hasColumn('chapters', 'study_guide'));
        $this->assertSame('<p>Live server content</p>', $chapter->fresh()->content);
        $this->assertNotEmpty($chapter->fresh()->study_guide);
        $this->assertSame(9, Chapter::where('subject_id', $chapter->subject_id)->count());
        $this->assertSame(1, $chapter->quizzes()->where('title', 'Vedic Age — Explained Practice MCQs')->count());
    }

    public function test_deployment_simplifies_only_the_unchanged_generated_vedic_guide(): void
    {
        $this->seed(HistoryStudyGuideSeeder::class);
        $originalGuides = json_decode(file_get_contents(database_path('seeders/history-study-guides.json')), true, flags: JSON_THROW_ON_ERROR);
        $originalVedicGuide = collect($originalGuides)->firstWhere('title', 'Vedic Age')['notes'];
        $chapter = Chapter::where('title', 'Vedic Age')->firstOrFail();
        $chapter->update(['study_guide' => $originalVedicGuide, 'content' => '<p>Keep my original chapter</p>']);
        $quiz = $chapter->quizzes()->firstOrFail();
        $questions = $quiz->questions;
        $migration = require database_path('migrations/2026_10_14_053257_simplify_generated_vedic_age_study_guide.php');
        $migration->up();
        $migration->down();
        $this->assertStringContainsString('A hymn is a song of praise.', $chapter->fresh()->study_guide['s'][0]['p'][0]);
        $this->assertSame('<p>Keep my original chapter</p>', $chapter->fresh()->content);
        $this->assertSame($questions, $quiz->fresh()->questions);
        $editedGuide = $chapter->fresh()->study_guide;
        $editedGuide['s'][0]['p'][0] = 'My own edited explanation';
        $chapter->update(['study_guide' => $editedGuide]);
        $migration->up();
        $this->assertSame($editedGuide, $chapter->fresh()->study_guide);
        $this->get('/ancient-history/vedic-age')->assertOk()->assertSee('My own edited explanation')->assertSee('Keep my original chapter');
    }

    public function test_remaining_guides_are_simplified_without_replacing_admin_edits_or_quizzes(): void
    {
        $this->seed(HistoryStudyGuideSeeder::class);
        $originalGuides = json_decode(file_get_contents(database_path('seeders/history-study-guides.json')), true, flags: JSON_THROW_ON_ERROR);
        $simpleGuides = json_decode(file_get_contents(database_path('seeders/history-simple-guides.json')), true, flags: JSON_THROW_ON_ERROR);
        $simpleGuides['Vedic Age'] = json_decode(file_get_contents(database_path('seeders/vedic-age-simple-guide.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($originalGuides as $guide) {
            Chapter::where('title', $guide['title'])->firstOrFail()->update(['study_guide' => $guide['notes']]);
        }
        $editedChapter = Chapter::where('title', 'Mughal Empire')->firstOrFail();
        $editedGuide = $editedChapter->study_guide;
        $editedGuide['s'][0]['p'][0] = 'My custom Mughal introduction';
        $editedChapter->update(['study_guide' => $editedGuide]);
        $originalQuizzes = Quiz::orderBy('id')->get()->map->getAttributes()->all();
        $migration = require database_path('migrations/2026_10_14_053532_simplify_remaining_generated_history_study_guides.php');
        $migration->up();
        $migration->up();
        $migration->down();
        foreach ($originalGuides as $guide) {
            $chapter = Chapter::where('title', $guide['title'])->firstOrFail();
            $expectedGuide = $guide['title'] === 'Mughal Empire' ? $editedGuide : $simpleGuides[$guide['title']];
            $this->assertSame($expectedGuide, $chapter->study_guide);
        }
        $this->assertSame($originalQuizzes, Quiz::orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame(9, Chapter::count());
    }
}
