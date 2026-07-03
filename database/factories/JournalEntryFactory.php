<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\Account;
use App\Models\Transaction;
use App\Enums\JournalEntryTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'account_id' => Account::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'type' => $this->faker->randomElement(JournalEntryTypes::cases()),
        ];
    }
}
