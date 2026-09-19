<?php

namespace App\Models;

use App\Enums\OrganizationLevel;
use App\Support\CellPhone;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'responsable_name', 'responsable_email', 'responsable_cell_phone',
    'address', 'city', 'province', 'postal_code', 'business_number', 'website', 'level', 'parent_id',
])]
class Organization extends Authenticatable
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => OrganizationLevel::class,
        ];
    }

    /**
     * Stored as digits only, presented as "(xxx) xxx-xxxx".
     */
    protected function responsableCellPhone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CellPhone::format($value),
            set: fn (?string $value) => CellPhone::normalize($value),
        );
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
     * this level or below, anywhere in the tree — no level above. Used to scope
     * the "interroger les membres" query for a responsable, matching what a
     * member holding a role here would themselves see by default. The organization
     * immediately above ("ma direction") is available separately, on demand —
     * see directionOrganizations().
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
     * Every organization this same person (by responsable email) is in charge of —
     * "les bottins dont il a la charge". Usually just this one.
     *
     * @return Collection<int, Organization>
     */
    public function organizationsManagedBySameResponsable(): Collection
    {
        return Organization::where('responsable_email', $this->responsable_email)->get();
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
            'name' => $this->responsable_name,
            'role' => 'Responsable de bottin',
            'organization' => $this->name,
            'responsable' => "{$this->responsable_name} ({$this->responsable_email})",
        ];
    }

    public function routeNotificationForMail(): string
    {
        return $this->responsable_email;
    }
}
