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
     * Every organization this member can query: for each role they hold, its
     * siblings (same level, same parent) plus everything below it.
     *
     * @return Collection<int, Organization>
     */
    public function visibleOrganizations(): Collection
    {
        $visible = Collection::make();

        foreach ($this->roles as $role) {
            $organization = $role->organization;

            $siblings = Organization::where('parent_id', $organization->parent_id)
                ->where('level', $organization->level)
                ->get();

            $visible = $visible->merge($siblings)->merge($organization->descendantOrganizations());
        }

        return $visible->unique('id');
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
