<?php

namespace Database\Factories;

use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_type_id' => TicketType::factory(),
            'user_id' => User::factory(),
            'code' => Str::uuid(),
            'status' => 'valid',
        ];
    }
}