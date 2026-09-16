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
     * This organization plus everything below it — what its responsable can query.
     *
     * @return Collection<int, Organization>
     */
    public function visibleOrganizations(): Collection
    {
        return Collection::make([$this])->merge($this->descendantOrganizations());
    }

    public function routeNotificationForMail(): string
    {
        return $this->responsable_email;
    }
}
