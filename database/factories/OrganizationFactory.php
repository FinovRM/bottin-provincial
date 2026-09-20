<?php

namespace Database\Factories;

use App\Enums\OrganizationGroup;
use App\Enums\OrganizationLevel;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'level' => OrganizationLevel::Provincial,
            'name' => fake()->company(),
            'responsable_name' => fake()->name(),
            'responsable_email' => fake()->unique()->safeEmail(),
        ];
    }

    public function provincial(): static
    {
        return $this->state([
            'parent_id' => null,
            'level' => OrganizationLevel::Provincial,
        ]);
    }

    public function regional(Organization $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'level' => OrganizationLevel::Regional,
        ]);
    }

    public function local(Organization $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'level' => OrganizationLevel::Local,
        ]);
    }

    public function ligue(): static
    {
        return $this->state([
            'group' => OrganizationGroup::Ligue,
        ]);
    }
}
