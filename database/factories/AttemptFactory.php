<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'user_id' => User::factory(),
            'answers' => [3],
            'score' => 1,
            'total' => 1,
            'seconds' => 20,
            'percentage' => 100,
        ];
    }
}
