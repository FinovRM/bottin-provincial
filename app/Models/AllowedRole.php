<?php

namespace App\Models;

use App\Enums\OrganizationGroup;
use Database\Factories\AllowedRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A role name a parent organization permits (beyond its minimum roles) for
 * each of its direct children, within one of its two groups.
 */
#[Fillable(['name', 'group'])]
class AllowedRole extends Model
{
    /** @use HasFactory<AllowedRoleFactory> */
    use HasFactory;

    /**
     * Matches the migration's column default — see Organization::$attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'group' => 'organisation',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => OrganizationGroup::class,
        ];
    }

    /**
     * The parent organization that permits this role for its children.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
