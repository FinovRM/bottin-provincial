<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PersonalFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalFilter>
 */
class PersonalFilterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filterable_type' => Organization::class,
            'filterable_id' => Organization::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'region_ids' => [],
            'local_ids' => [],
            'roles' => [],
        ];
    }
}
