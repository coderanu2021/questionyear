<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'title' => fake()->sentence(3),
            'status' => 'published',
            'lessons' => 1,
            'content' => '<p>Chapter notes.</p>',
        ];
    }
}
