<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'role' => fake()->jobTitle(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cell_phone' => fake()->phoneNumber(),
        ];
    }
}
