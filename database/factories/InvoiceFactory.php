<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => fake()->unique()->regexify('[A-Z]{3}[0-9]{7}'),
            'client_name' => fake()->name(),
            'client_email' => fake()->email(),
            'client_phone' => fake()->phoneNumber(),
            'amount' => fake()->numberBetween(100, 10000),
            'currency' => 'BDT',
            'status' => fake()->randomElement(['pending', 'paid', 'canceled']),
            'redirect_url' => fake()->url(),
            'cancel_url' => fake()->url(),
            'webhook_url' => fake()->url(),
        ];
    }
}
