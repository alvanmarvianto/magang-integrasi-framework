<?php

namespace Database\Factories;

use App\Models\Stream;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stream>
 */
class StreamFactory extends Factory
{
    protected $model = Stream::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $streamNames = [
            'sp',
            'mi',
            'ssk',
            'moneter',
            'market',
            'internal bi',
            'external bi',
            'middleware'
        ];

        return [
            'stream_name' => $this->faker->unique()->randomElement($streamNames),
        ];
    }
}
