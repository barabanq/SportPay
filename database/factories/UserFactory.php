<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '+7999' . fake()->numerify('#######'),
            'wallet_balance' => 0.00,
            'rating' => 5.00,
            'status' => UserStatus::cases()[0]->value,
            'remember_token' => Str::random(10),
        ];
    }
}
