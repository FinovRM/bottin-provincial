<?php

namespace App\Models;

use Database\Factories\MinimumRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A role name a parent organization requires each of its direct children to fill.
 */
#[Fillable(['name'])]
class MinimumRole extends Model
{
    /** @use HasFactory<MinimumRoleFactory> */
    use HasFactory;

    /**
     * The parent organization that requires this role of its children.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
