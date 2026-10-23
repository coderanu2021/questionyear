<?php

namespace Database\Factories;

use App\Models\JobPushSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPushSubscription>
 */
class JobPushSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/'.fake()->uuid();

        return [
            'endpoint' => $endpoint,
            'endpoint_hash' => hash('sha256', $endpoint),
            'public_key' => fake()->regexify('[A-Za-z0-9_-]{87}'),
            'auth_token' => fake()->regexify('[A-Za-z0-9_-]{22}'),
            'content_encoding' => 'aes128gcm',
        ];
    }
}
