<?php

namespace Database\Factories;

use App\Enums\OrganizationGroup;
use App\Enums\OrganizationLevel;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use WeakMap;

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
     * The former organization columns tests may still pass, and the
     * responsable field each one now fills.
     */
    private const RESPONSABLE_FIELDS = [
        'responsable_name' => 'name',
        'responsable_email' => 'email',
        'responsable_cell_phone' => 'cell_phone',
    ];

    /**
     * Every organization gets one responsable, taking any responsable_* attribute
     * given to the organization.
     */
    public function configure(): static
    {
        /** @var WeakMap<Organization, array<string, mixed>> $given */
        $given = new WeakMap;

        return $this
            ->afterMaking(function (Organization $organization) use ($given) {
                $attributes = array_intersect_key($organization->getAttributes(), self::RESPONSABLE_FIELDS);

                foreach (array_keys($attributes) as $attribute) {
                    unset($organization->{$attribute});
                }

                $given[$organization] = array_combine(
                    array_map(fn ($attribute) => self::RESPONSABLE_FIELDS[$attribute], array_keys($attributes)),
                    $attributes,
                );
            })
            ->afterCreating(fn (Organization $organization) => $organization->responsables()->create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                ...$given[$organization],
            ]));
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
