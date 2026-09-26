<?php

namespace App\Models;

use App\Enums\OrganizationGroup;
use App\Enums\OrganizationLevel;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'legal_name',
    'address', 'city', 'province', 'postal_code', 'business_number', 'website', 'level', 'group', 'parent_id',
])]
class Organization extends Authenticatable
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, Notifiable;

    /**
     * Matches the migration's column default. Without this, a newly created
     * instance that didn't set 'group' explicitly only gets it back from a
     * fresh read of the database — this keeps the in-memory object correct too.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'group' => 'organisation',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => OrganizationLevel::class,
            'group' => OrganizationGroup::class,
        ];
    }

    /**
     * The full mailing address (street, city, province, postal code) as a single
     * line, or null if none of those fields are filled in.
     */
    public function fullPostalAddress(): ?string
    {
        $line = collect([$this->address, $this->city, $this->province])->filter()->implode(', ');

        if ($this->postal_code) {
            $line = trim("{$line} {$this->postal_code}");
        }

        return $line !== '' ? $line : null;
    }

    /**
     * The people in charge of this organization's bottin.
     *
     * @return HasMany<Responsable, $this>
     */
    public function responsables(): HasMany
    {
        return $this->hasMany(Responsable::class)->orderBy('name');
    }

    /**
     * The responsable using the bottin right now as this organization — the
     * courriel they logged in with — or, when none is known, the first one.
     */
    public function currentResponsable(): ?Responsable
    {
        return $this->responsables->firstWhere('email', session('responsable_email'))
            ?? $this->responsables->first();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    /**
     * @return HasMany<Organization, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Organization::class, 'parent_id')->orderBy('name');
    }

    /**
     * @return MorphMany<PersonalFilter, $this>
     */
    public function personalFilters(): MorphMany
    {
        return $this->morphMany(PersonalFilter::class, 'filterable');
    }

    /**
     * @return HasMany<MemberRole, $this>
     */
    public function memberRoles(): HasMany
    {
        return $this->hasMany(MemberRole::class);
    }

    /**
     * The role names this organization requires each of its direct children to fill.
     *
     * @return HasMany<MinimumRole, $this>
     */
    public function minimumRoles(): HasMany
    {
        return $this->hasMany(MinimumRole::class);
    }

    /**
     * The role names this organization permits (beyond its minimum roles)
     * for each of its direct children.
     *
     * @return HasMany<AllowedRole, $this>
     */
    public function allowedRoles(): HasMany
    {
        return $this->hasMany(AllowedRole::class);
    }

    /**
     * The role names usable by this organization's own members, as set by its
     * parent for organizations in this same group (minimum roles union allowed
     * roles). Null when unrestricted — no parent, or the parent hasn't
     * configured any roles yet for this group.
     *
     * @return ?array<int, string>
     */
    public function usableRoleNames(): ?array
    {
        if (! $this->parent) {
            return null;
        }

        $names = $this->parent->minimumRoles->where('group', $this->group)->pluck('name')
            ->merge($this->parent->allowedRoles->where('group', $this->group)->pluck('name'))
            ->unique()
            ->sort()
            ->values();

        return $names->isNotEmpty() ? $names->all() : null;
    }

    /**
     * A minimum/allowed role of this organization was deleted: remove that
     * role from every direct child's members in that same group, deleting a
     * member outright if they're left without any role. Returns how many
     * member roles were removed.
     */
    public function removeChildMemberRolesNamed(string $roleName, OrganizationGroup $group): int
    {
        $memberRoles = MemberRole::whereIn('organization_id', $this->children()->where('group', $group)->pluck('id'))
            ->where('role', $roleName)
            ->with('member')
            ->get();

        $affectedOrganizationIds = $memberRoles->pluck('organization_id')->unique();

        foreach ($memberRoles as $memberRole) {
            $member = $memberRole->member;
            $memberRole->delete();

            if ($member->roles()->doesntExist()) {
                $member->delete();
            }
        }

        Organization::whereIn('id', $affectedOrganizationIds)->get()->each->touch();

        return $memberRoles->count();
    }

    /**
     * A minimum/allowed role of this organization was renamed: rename that
     * role on every direct child's members in that same group to match.
     * Returns how many member roles were renamed.
     */
    public function renameChildMemberRoles(string $oldName, string $newName, OrganizationGroup $group): int
    {
        $affectedOrganizationIds = MemberRole::whereIn('organization_id', $this->children()->where('group', $group)->pluck('id'))
            ->where('role', $oldName)
            ->pluck('organization_id')
            ->unique();

        $count = MemberRole::whereIn('organization_id', $affectedOrganizationIds)
            ->where('role', $oldName)
            ->update(['role' => $newName]);

        Organization::whereIn('id', $affectedOrganizationIds)->get()->each->touch();

        return $count;
    }

    public function canCreateChildren(): bool
    {
        return $this->level->childLevel() !== null;
    }

    /**
     * All organizations below this one in the tree, at every depth.
     *
     * @return Collection<int, Organization>
     */
    public function descendantOrganizations(): Collection
    {
        $descendants = Collection::make();
        $frontier = $this->children()->get();

        while ($frontier->isNotEmpty()) {
            $descendants = $descendants->merge($frontier);
            $frontier = Organization::whereIn('parent_id', $frontier->pluck('id'))->get();
        }

        return $descendants;
    }

    /**
     * This organization plus everything below it — what its responsable can query
     * and manage (create/edit/delete children, view its own organization profile).
     *
     * @return Collection<int, Organization>
     */
    public function visibleOrganizations(): Collection
    {
        return Collection::make([$this])->merge($this->descendantOrganizations());
    }

    /**
     * Every organization visible to a member of this organization: everything at
     * this level or below, anywhere in the tree, in either group — no level above.
     * Used to scope the "interroger les membres" query for a responsable, matching
     * what a member holding a role here would themselves see by default. The
     * organization immediately above ("ma direction") is available separately, on
     * demand — see directionOrganizations().
     *
     * @return Collection<int, Organization>
     */
    public function visibleToMembers(): Collection
    {
        return Organization::whereIn('level', $this->level->andBelow())->get();
    }

    /**
     * The organization immediately above this one — "ma direction" — or an empty
     * collection if this is a provincial (top-level) organization.
     *
     * @return Collection<int, Organization>
     */
    public function directionOrganizations(): Collection
    {
        return $this->parent ? Collection::make([$this->parent]) : Collection::make();
    }

    /**
     * Every organization the current responsable (by courriel) is in charge of —
     * "les bottins dont il a la charge". Usually just this one.
     *
     * @return Collection<int, Organization>
     */
    public function organizationsManagedBySameResponsable(): Collection
    {
        $email = $this->currentResponsable()?->email;

        return Organization::whereHas('responsables', fn ($responsables) => $responsables->where('email', $email))->get()
            ->whenEmpty(fn () => Collection::make([$this]));
    }

    /**
     * Among every organization this person is in charge of, the most senior one.
     * Used when an éditeur uses the "Bottin" link: their access level there is
     * that of their highest organization, not necessarily the one they're
     * currently editing.
     */
    public function highestManagedOrganization(): self
    {
        return $this->organizationsManagedBySameResponsable()
            ->sortBy(fn (self $organization) => $organization->level->rank())
            ->first();
    }

    /**
     * Who's connected, for the black identity banner under the menu, on pages
     * scoped to this organization itself (not the highest one it manages).
     *
     * @return array{name: string, role: string, organization: string, responsable: string}
     */
    public function identity(): array
    {
        return [
            'name' => $this->currentResponsable()?->name ?? '—',
            'role' => 'Responsable de bottin',
            'organization' => $this->name,
            'responsable' => $this->responsablesSummary(),
        ];
    }

    /**
     * Every responsable as "Name (courriel)", for the identity banner.
     */
    public function responsablesSummary(): string
    {
        return $this->responsables->map(fn (Responsable $responsable) => "{$responsable->name} ({$responsable->email})")
            ->whenEmpty(fn ($summary) => $summary->push('—'))
            ->implode(', ');
    }

    /**
     * @return array<int, string>
     */
    public function routeNotificationForMail(): array
    {
        return $this->responsables->pluck('email')->all();
    }
}
