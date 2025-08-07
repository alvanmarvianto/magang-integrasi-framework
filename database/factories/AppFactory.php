<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Stream;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\App>
 */
class AppFactory extends Factory
{
    protected $model = App::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appNames = [
            'Core Banking System',
            'Payment Gateway',
            'Risk Management System',
            'Customer Portal',
            'Admin Dashboard',
            'Transaction Monitor',
            'Fraud Detection',
            'Reporting Engine',
            'Data Warehouse',
            'Mobile Banking App',
            'ATM Network',
            'Card Management',
            'Loan Origination',
            'Credit Scoring',
            'Compliance Monitor',
            'Settlement System',
            'Clearing House',
            'Market Data Feed',
            'Trading Platform',
            'Regulatory Reporting'
        ];

        return [
            'app_name' => $this->faker->unique()->randomElement($appNames),
            'stream_id' => Stream::factory(),
            'description' => $this->faker->sentence(10),
        ];
    }
}
