<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['General', 'VIP', 'Premium']),
            'price' => fake()->randomFloat(2, 10, 200),
            'quantity' => fake()->numberBetween(50, 500),
        ];
    }
}