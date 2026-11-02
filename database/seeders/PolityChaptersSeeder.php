<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PolityChaptersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chapters = json_decode(file_get_contents(__DIR__.'/polity-chapters.json'), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($chapters): void {
            $subject = Subject::firstOrCreate(
                ['slug' => 'political-science'],
                [
                    'name' => 'Political Science',
                    'category' => 'Humanities',
                    'description' => 'Indian Polity and Constitution notes for UPSC, SSC, Railway and State PSC exams.',
                ]
            );

            foreach ($chapters as $item) {
                $chapter = Chapter::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'title' => $item['title'],
                    ],
                    [
                        'content' => $item['content'],
                        'description' => Str::limit(strip_tags($item['content']), 160),
                        'lessons' => 1,
                        'status' => 'published',
                    ]
                );

                if ($item['seo_title'] || $item['seo_desc']) {
                    DB::table('page_seo')->updateOrInsert(
                        ['page_key' => 'chapter:'.$chapter->id],
                        [
                            'meta_title' => $item['seo_title'],
                            'meta_description' => $item['seo_desc'],
                            'meta_keywords' => $item['seo_keywords'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                $this->command?->info('Ch'.$item['num'].': '.$chapter->title.' | '.Str::limit($item['seo_title'], 60));
            }
        });
    }
}
