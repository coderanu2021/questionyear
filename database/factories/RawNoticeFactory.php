<?php

namespace Database\Factories;

use App\Models\JobSource;
use App\Models\RawNotice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RawNotice>
 */
class RawNoticeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => JobSource::factory(),
            'url' => fake()->url(),
            'title' => fake()->sentence(),
            'hash' => hash('sha256', fake()->unique()->sentence()),
            'raw_text' => fake()->paragraphs(3, true),
            'fetched_at' => now(),
            'extraction_status' => 'pending',
            'extraction_attempts' => 0,
            'extracted_at' => null,
            'last_extraction_error' => null,
        ];
    }
}
