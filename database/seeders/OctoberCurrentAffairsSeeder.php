<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OctoberCurrentAffairsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = json_decode(file_get_contents(__DIR__.'/october-current-affairs.json'), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($days): void {
            $subject = Subject::firstOrCreate(['slug' => 'current-affairs'], ['name' => 'Current Affairs', 'category' => 'General', 'description' => 'Daily current affairs questions and answers']);

            foreach ($days as $day) {
                $quiz = Quiz::firstOrCreate([
                    'subject_id' => $subject->id,
                    'chapter_id' => null,
                    'title' => $day['title'],
                ], [
                    'quiz_date' => $day['date'],
                    'status' => 'published',
                    'duration' => 15,
                    'passing_score' => 40,
                    'questions' => $day['questions'],
                ]);

                DB::table('page_seo')->insertOrIgnore([
                    'page_key' => 'quiz:'.$quiz->id,
                    'meta_title' => $day['meta_title'],
                    'meta_description' => $day['meta_description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command?->info($quiz->title.' | '.count($quiz->questions).' questions | '.$quiz->publicUrl());
            }
        });
    }
}
