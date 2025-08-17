<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Forum>
 */
class ForumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . 'フォーラム',
            'unit_id' => 1,
            'tenant_id' => 1,
            'description' => $this->faker->optional()->text(100),
            'visibility' => $this->faker->randomElement(['public', 'private']),
            'status' => 'active',
        ];
    }
}
