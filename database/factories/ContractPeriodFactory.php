<?php

namespace Database\Factories;

use App\Models\ContractPeriod;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContractPeriod>
 */
class ContractPeriodFactory extends Factory
{
    protected $model = ContractPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+6 months');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 year');
        $budgetType = $this->faker->randomElement(['AO', 'RI']);
        $paymentStatuses = [
            'paid', 'ba_process', 'mka_process', 'settlement_process', 
            'addendum_process', 'not_due', 'has_issue', 'unpaid', 
            'reserved_hr', 'contract_moved'
        ];

        return [
            'contract_id' => Contract::factory(),
            'period_name' => 'Period ' . $this->faker->numberBetween(1, 10),
            'budget_type' => $budgetType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_value_rp' => $this->faker->randomFloat(2, 10000000, 500000000),
            'payment_value_non_rp' => $this->faker->randomFloat(2, 10000, 500000),
            'payment_status' => $this->faker->randomElement($paymentStatuses),
        ];
    }
}
