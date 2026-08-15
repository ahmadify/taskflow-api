<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'owner_id' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'start_date' => $startDate,
            'due_date' => fake()->dateTimeBetween($startDate, '+6 months'),
        ];
    }
}
