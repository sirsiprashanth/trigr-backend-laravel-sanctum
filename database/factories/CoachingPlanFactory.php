<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\CoachingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoachingPlanFactory extends Factory
{
    protected $model = CoachingPlan::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'coach_id' => User::factory(),
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'contract_terms' => $this->faker->paragraph,
            'price' => $this->faker->randomFloat(2, 100, 1000),
            'start_date' => now()->addDay(),
            'end_date' => now()->addMonths(3),
            'status' => $this->faker->randomElement(['draft', 'in_progress', 'completed'])
        ];
    }
}
