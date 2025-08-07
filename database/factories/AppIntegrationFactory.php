<?php

namespace Database\Factories;

use App\Models\AppIntegration;
use App\Models\App;
use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppIntegration>
 */
class AppIntegrationFactory extends Factory
{
    protected $model = AppIntegration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_app_id' => App::factory(),
            'target_app_id' => App::factory(),
            'connection_type_id' => ConnectionType::factory(),
            'inbound_description' => $this->faker->sentence(8),
            'outbound_description' => $this->faker->sentence(8),
            'endpoint' => $this->faker->url(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AppIntegration $appIntegration) {
            // Ensure source and target apps are different
            if ($appIntegration->source_app_id === $appIntegration->target_app_id) {
                $appIntegration->target_app_id = App::factory()->create()->app_id;
            }
        });
    }
}
