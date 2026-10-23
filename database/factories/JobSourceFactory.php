<?php

namespace Database\Factories;

use App\Models\JobSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobSource>
 */
class JobSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Recruitment',
            'url' => fake()->unique()->url(),
            'type' => 'html',
            'state' => fake()->optional()->state(),
            'category' => 'recruitment',
            'parser' => 'html',
            'active' => true,
            'trusted' => false,
            'last_run_at' => null,
            'terms_url' => null,
            'terms_reviewed_at' => null,
            'terms_allows_automated_access' => false,
        ];
    }
}
