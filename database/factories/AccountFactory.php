<?php

namespace Database\Factories;

use App\Models\Account;
use App\Enums\AccountTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->numberBetween(1, 9999),
            'type' => $this->faker->randomElement(AccountTypes::cases()),
            'is_active' => true,
        ];
    }
}
