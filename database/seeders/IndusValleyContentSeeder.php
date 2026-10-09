<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class IndusValleyContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/indus-valley-content.json'), true, flags: JSON_THROW_ON_ERROR);
        $subject = Subject::firstOrCreate(['slug' => 'history'], ['name' => 'History', 'category' => 'General', 'description' => 'Explore History']);
        $chapter = Chapter::firstOrCreate(['subject_id' => $subject->id, 'title' => $data['title']], ['category' => $data['category'], 'lessons' => 1, 'status' => 'published']);
        if (blank($chapter->content)) {
            $chapter->update(['content' => $data['content'], 'category' => $chapter->category ?? $data['category']]);
        }
    }
}
