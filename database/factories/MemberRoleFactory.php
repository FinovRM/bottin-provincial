<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberRole>
 */
class MemberRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'organization_id' => Organization::factory(),
            'role' => fake()->jobTitle(),
        ];
    }
}
