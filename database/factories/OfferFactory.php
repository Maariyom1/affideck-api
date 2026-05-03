<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['cpa', 'cpc', 'cpv']),
            'payout' => fake()->randomFloat(2, 1, 100),
            'status' => 'published',
        ];
    }
}
