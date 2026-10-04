<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'title' => fake()->sentence(3),
            'duration' => 15,
            'passing_score' => 40,
            'questions' => [['q' => 'What is 2 + 2?', 'o' => ['1', '2', '3', '4'], 'c' => 3, 'explanation' => 'Two plus two is four.']],
        ];
    }
}
