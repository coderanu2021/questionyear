<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/curriculum.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($data['S'] as [$name, $category, $description, $chapters]) {
            $subject = Subject::firstOrCreate(['name' => $name], ['slug' => Str::slug(str_replace('&', '', $name)), 'category' => $category, 'description' => $description]);
            foreach (explode('|', $chapters) as $index => $title) {
                $key = $subject->slug.':'.$index;
                $chapter = Chapter::firstOrCreate(['subject_id' => $subject->id, 'title' => $title], ['status' => 'published', 'notes' => $data['LN'][$key] ?? null, 'description' => $data['LN'][$key]['sub'] ?? '', 'lessons' => count($data['LN'][$key]['s'] ?? []) ?: 1]);
                if (isset($data['QB'][$key])) {
                    Quiz::firstOrCreate(['chapter_id' => $chapter->id, 'title' => $title.' Quiz'], ['questions' => array_map(fn ($q) => ['q' => $q[0], 'o' => $q[1], 'c' => $q[2], 'explanation' => $q[3] ?? ''], $data['QB'][$key]), 'duration' => 15, 'passing_score' => 40]);
                }
            }
        }
    }
}
