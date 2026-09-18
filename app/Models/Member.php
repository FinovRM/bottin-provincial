<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'cell_phone'])]
class Member extends Authenticatable
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, Notifiable;

    /**
     * @return HasMany<MemberRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(MemberRole::class);
    }

    /**
     * Find the person already registered under this email, or create a new one.
     * Name and cell phone are only ever set on first entry — an existing person's
     * data is never overwritten by a later role submission.
     */
    public static function findOrCreateByEmail(string $email, string $name, ?string $cellPhone): self
    {
        return static::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'cell_phone' => $cellPhone],
        );
    }

    /**
     * Every organization this member can query: for each role they hold, everything
     * at that role's level or below, anywhere in the tree, plus that role's own
     * parent organization. A regional member sees every region and every local
     * organization, plus their own provincial office; a local member sees every
     * local organization, plus their own regional direction.
     *
     * @return Collection<int, Organization>
     */
    public function visibleOrganizations(): Collection
    {
        $organizations = $this->roles
            ->flatMap(fn (MemberRole $role) => $role->organization->visibleToMembers())
            ->unique('id')
            ->values()
            ->all();

        return new Collection($organizations);
    }

    /**
     * The organization(s) immediately above this member's roles — "ma direction".
     * A member holding roles at different levels may have more than one.
     *
     * @return Collection<int, Organization>
     */
    public function directionOrganizations(): Collection
    {
        $organizations = $this->roles
            ->map(fn (MemberRole $role) => $role->organization->parent)
            ->filter()
            ->unique('id')
            ->values()
            ->all();

        return new Collection($organizations);
    }

    /**
     * Among every role this member holds, the one at their most senior organization.
     * Used to show a single "role / organization" identity for this member.
     */
    public function primaryRole(): ?MemberRole
    {
        return $this->roles->sortBy(fn (MemberRole $role) => $role->organization->level->rank())->first();
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
