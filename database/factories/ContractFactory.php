<?php

namespace Database\Factories;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencyType = $this->faker->randomElement(['rp', 'non_rp']);
        
        return [
            'title' => $this->faker->sentence(5),
            'contract_number' => 'CT-' . $this->faker->unique()->numerify('####-###'),
            'currency_type' => $currencyType,
            'contract_value_rp' => $currencyType === 'rp' ? $this->faker->randomFloat(2, 100000000, 5000000000) : null,
            'contract_value_non_rp' => $currencyType === 'non_rp' ? $this->faker->randomFloat(2, 100000, 5000000) : null,
            'lumpsum_value_rp' => $this->faker->randomFloat(2, 50000000, 1000000000),
            'unit_value_rp' => $this->faker->randomFloat(2, 1000000, 100000000),
            'description' => $this->faker->paragraph(),
        ];
    }
}
