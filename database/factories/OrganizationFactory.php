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
        ];
    }

    /**
     * Every organization gets one responsable. Tests may still pass the old
     * responsable_name / responsable_email / responsable_cell_phone attributes:
     * they're set on that responsable instead.
     */
    public function configure(): static
    {
        $pending = new \WeakMap;
        $fields = ['responsable_name' => 'name', 'responsable_email' => 'email', 'responsable_cell_phone' => 'cell_phone'];

        return $this
            ->afterMaking(function (Organization $organization) use ($pending, $fields) {
                $responsable = [];

                foreach ($fields as $attribute => $field) {
                    if (array_key_exists($attribute, $organization->getAttributes())) {
                        $responsable[$field] = $organization->getAttributes()[$attribute];
                        unset($organization->{$attribute});
                    }
                }

                $pending[$organization] = $responsable;
            })
            ->afterCreating(function (Organization $organization) use ($pending) {
                $organization->responsables()->create([
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    ...($pending[$organization] ?? []),
                ]);
            });
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
