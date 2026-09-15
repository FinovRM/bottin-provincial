<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['organization_id', 'role', 'name', 'email', 'cell_phone'])]
class Member extends Authenticatable
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, Notifiable;

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Organizations at the same level under the same parent as this member's own
     * organization, plus everything below that organization — what this member can query.
     *
     * @return Collection<int, Organization>
     */
    public function visibleOrganizations(): Collection
    {
        $organization = $this->organization;

        $siblings = Organization::where('parent_id', $organization->parent_id)
            ->where('level', $organization->level)
            ->get();

        return $siblings->merge($organization->descendantOrganizations())->unique('id');
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
