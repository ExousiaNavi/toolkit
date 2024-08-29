<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => 1,
            'currency' => "BDT",
            'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
            'email' => 'test@email.com',
            'password' => 'password'
        ];
    }
}
