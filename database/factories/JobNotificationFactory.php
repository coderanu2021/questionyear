<?php

namespace Database\Factories;

use App\Models\JobNotification;
use App\Models\RawNotice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobNotification>
 */
class JobNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'slug' => fake()->unique()->slug(),
            'organization' => fake()->company(),
            'summary' => fake()->sentence(),
            'vacancies' => fake()->optional()->numberBetween(1, 5000),
            'state' => fake()->optional()->state(),
            'qualification_level' => fake()->randomElement(['10th-pass', '12th-pass', 'graduate']),
            'advertisement_no' => fake()->bothify('ADV-####/2026'),
            'fee' => null,
            'age_limit' => null,
            'start_date' => null,
            'last_date' => null,
            'pdf_url' => null,
            'official_url' => 'https://example.gov.in/recruitment',
            'status' => 'pending',
            'source_checked_at' => now(),
            'raw_notice_id' => RawNotice::factory(),
        ];
    }
}
