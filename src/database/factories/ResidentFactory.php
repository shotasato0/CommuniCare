<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resident>
 */
class ResidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'unit_id' => 1,
            'tenant_id' => 1,
            'meal_support' => $this->faker->randomElement(['自立', '一部介助', '全介助']),
            'toilet_support' => $this->faker->randomElement(['自立', '一部介助', '全介助']),
            'bathing_support' => $this->faker->randomElement(['自立', '一部介助', '全介助']),
            'mobility_support' => $this->faker->randomElement(['自立', '一部介助', '全介助']),
            'memo' => $this->faker->optional()->text(200),
        ];
    }
}
