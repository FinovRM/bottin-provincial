<?php

namespace Database\Factories;

use App\Enums\OrganizationGroup;
use App\Enums\OrganizationLevel;
use App\Models\Organization;
use App\Models\Responsable;
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
        ];
    }

    /**
     * Every organization gets one responsable, unless one was given with withResponsable().
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Organization $organization) {
            if ($organization->responsables()->doesntExist()) {
                Responsable::factory()->for($organization)->create();
            }
        });
    }

    /**
     * Its responsable, with these attributes (name, email, cell_phone…).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function withResponsable(array $attributes): static
    {
        return $this->has(Responsable::factory()->state($attributes));
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
