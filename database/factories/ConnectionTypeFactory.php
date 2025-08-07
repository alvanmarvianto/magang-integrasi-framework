<?php

namespace Database\Factories;

use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConnectionType>
 */
class ConnectionTypeFactory extends Factory
{
    protected $model = ConnectionType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $connectionTypes = [
            'direct',
            'sftp',
            'soa',
            'soa-sftp'
        ];

        return [
            'type_name' => $this->faker->unique()->randomElement($connectionTypes),
        ];
    }
}
