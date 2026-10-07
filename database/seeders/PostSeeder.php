<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        Post::firstOrCreate(['slug' => 'build-a-consistent-study-routine'], [
            'type' => 'blog',
            'title' => 'Build a consistent study routine',
            'excerpt' => 'Simple ways to make daily practice part of your exam preparation.',
            'content' => "Choose one topic each day and set a small, realistic study goal. Read the chapter notes before attempting a quiz.\n\nReview your incorrect answers and revisit difficult topics regularly. A short, consistent routine helps you track progress and build confidence.",
            'status' => 'draft',
        ]);
    }
}
