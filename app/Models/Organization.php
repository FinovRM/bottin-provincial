<?php

namespace App\Models;

use App\Enums\OrganizationLevel;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'responsable_first_name', 'responsable_last_name', 'responsable_email',
    'address', 'business_number', 'website', 'level', 'parent_id',
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
        return $this->hasMany(Organization::class, 'parent_id');
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

    public function routeNotificationForMail(): string
    {
        return $this->responsable_email;
    }
}
