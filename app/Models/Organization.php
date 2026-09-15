<?php

namespace App\Models;

use App\Enums\OrganizationLevel;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'responsable_first_name', 'responsable_last_name', 'responsable_email', 'level'])]
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

    public function canCreateChildren(): bool
    {
        return $this->level->childLevel() !== null;
    }

    public function routeNotificationForMail(): string
    {
        return $this->responsable_email;
    }
}
