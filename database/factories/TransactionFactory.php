<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_type' => fake()->randomElement(['sms', 'manual']),
            'sim' => fake()->randomElement(['sim1', 'sim2']),
            'message' => fake()->sentence(),
            'provider' => fake()->randomElement(['bkash-merchant', 'bkash-personal', 'nagad-personal', 'bank-city', 'bank-ebl']),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'mobile' => fake()->phoneNumber(),
            'trxid' => fake()->unique()->regexify('[A-Z0-9]{10}'),
            'balance' => fake()->randomFloat(2, 1000, 100000),
            'status' => fake()->randomElement(['approved', 'review', 'rejected']),
        ];
    }
}
