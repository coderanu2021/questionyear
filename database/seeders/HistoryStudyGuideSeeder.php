<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoryStudyGuideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guides = json_decode(file_get_contents(__DIR__.'/history-study-guides.json'), true, flags: JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($guides): void {
            $subject = Subject::firstOrCreate(['slug' => 'history'], ['name' => 'History', 'category' => 'Humanities', 'description' => 'Ancient, medieval and modern Indian history.']);
            foreach ($guides as $guide) {
                $chapter = Chapter::firstOrCreate(['subject_id' => $subject->id, 'title' => $guide['title']], [
                    'category' => $guide['category'], 'status' => 'published',
                    'description' => $guide['notes']['sub'], 'lessons' => count($guide['notes']['s']),
                ]);
                if (blank($chapter->study_guide)) {
                    $chapter->update(['study_guide' => $guide['notes']]);
                }
                if (blank($chapter->category)) {
                    $chapter->update(['category' => $guide['category']]);
                }
                Quiz::firstOrCreate(['chapter_id' => $chapter->id, 'title' => $guide['title'].' — Explained Practice MCQs'], [
                    'questions' => $guide['questions'], 'duration' => 15, 'passing_score' => 40,
                ]);
            }
        });
    }
}
