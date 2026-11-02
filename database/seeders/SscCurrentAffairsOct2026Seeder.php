<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SscCurrentAffairsOct2026Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/ssc-october-2026-mcq.json'), true, flags: JSON_THROW_ON_ERROR);
        $item = $data[0];

        DB::transaction(function () use ($item): void {
            $subject = Subject::firstOrCreate(
                ['slug' => 'current-affairs'],
                ['name' => 'Current Affairs', 'category' => 'General', 'description' => 'Daily current affairs questions and answers']
            );

            // Delete the 16 old section-wise quizzes if they exist
            $oldTitles = Quiz::where('subject_id', $subject->id)
                ->where('title', 'LIKE', 'SSC CA Oct 2026 –%')
                ->pluck('id');

            if ($oldTitles->isNotEmpty()) {
                DB::table('page_seo')->whereIn('page_key', $oldTitles->map(fn ($id) => 'quiz:'.$id))->delete();
                Quiz::whereIn('id', $oldTitles)->delete();
            }

            $quiz = Quiz::updateOrCreate(
                [
                    'subject_id' => $subject->id,
                    'chapter_id' => null,
                    'title' => $item['title'],
                ],
                [
                    'quiz_date' => $item['date'],
                    'status' => 'published',
                    'duration' => 30,
                    'passing_score' => 40,
                    'questions' => $item['questions'],
                ]
            );

            DB::table('page_seo')->updateOrInsert(
                ['page_key' => 'quiz:'.$quiz->id],
                [
                    'meta_title' => $item['meta_title'],
                    'meta_description' => $item['meta_description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command?->info($quiz->title.' | '.count($quiz->questions).' questions | '.$quiz->publicUrl());
        });
    }
}
