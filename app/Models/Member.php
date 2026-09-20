<?php

namespace App\Models;

use App\Support\CellPhone;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection as SupportCollection;

#[Fillable(['name', 'email', 'cell_phone'])]
class Member extends Authenticatable
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, Notifiable;

    /**
     * Stored as digits only, presented as "(xxx) xxx-xxxx".
     */
    protected function cellPhone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CellPhone::format($value),
            set: fn (?string $value) => CellPhone::normalize($value),
        );
    }

    /**
     * @return HasMany<MemberRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(MemberRole::class);
    }

    /**
     * @return MorphMany<PersonalFilter, $this>
     */
    public function personalFilters(): MorphMany
    {
        return $this->morphMany(PersonalFilter::class, 'filterable');
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
     * Every organization this member can query: for each considered role, everything
     * at that role's level or below, anywhere in the tree, plus that role's own
     * parent organization. A regional member sees every region and every local
     * organization, plus their own provincial office; a local member sees every
     * local organization, plus their own regional direction.
     *
     * @return Collection<int, Organization>
     */
    public function visibleOrganizations(): Collection
    {
        $organizations = $this->consideredRoles()
            ->flatMap(fn (MemberRole $role) => $role->organization->visibleToMembers())
            ->unique('id')
            ->values()
            ->all();

        return new Collection($organizations);
    }

    /**
     * Same reach as visibleOrganizations(), but combining both groups — see
     * Organization::visibleToMembersAcrossGroups().
     *
     * @return Collection<int, Organization>
     */
    public function visibleOrganizationsAcrossGroups(): Collection
    {
        $organizations = $this->consideredRoles()
            ->flatMap(fn (MemberRole $role) => $role->organization->visibleToMembersAcrossGroups())
            ->unique('id')
            ->values()
            ->all();

        return new Collection($organizations);
    }

    /**
     * The organization(s) immediately above the considered role(s) — "ma direction".
     * A member holding roles at different levels may have more than one, unless a
     * single role is being considered.
     *
     * @return Collection<int, Organization>
     */
    public function directionOrganizations(): Collection
    {
        $organizations = $this->consideredRoles()
            ->map(fn (MemberRole $role) => $role->organization->parent)
            ->filter()
            ->unique('id')
            ->values()
            ->all();

        return new Collection($organizations);
    }

    /**
     * The role chosen at login when this member held several — everything this
     * member sees is then scoped to this role alone. Null if only one role was
     * held, in which case there was nothing to choose.
     */
    public function activeRole(): ?MemberRole
    {
        $roleId = session('active_member_role_id');

        return $roleId ? $this->roles->firstWhere('id', $roleId) : null;
    }

    /**
     * The role(s) that should scope this member's access: just the active role
     * when one was chosen among several, otherwise every role they hold.
     *
     * @return SupportCollection<int, MemberRole>
     */
    private function consideredRoles(): SupportCollection
    {
        $active = $this->activeRole();

        return $active ? collect([$active]) : $this->roles;
    }

    /**
     * Among the considered role(s), the one at the most senior organization.
     * Used to show a single "role / organization" identity for this member.
     */
    public function primaryRole(): ?MemberRole
    {
        return $this->consideredRoles()->sortBy(fn (MemberRole $role) => $role->organization->level->rank())->first();
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
