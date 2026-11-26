<?php

namespace App\Models;

use App\Support\CellPhone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * A visitor's request, made from the declaration page, to change the
 * coordinates attached to some of their roles or to be removed from the bottin.
 * Each role is stored as {key, organization, role} — key as in VisitorIdentities.
 */
#[Fillable(['type', 'name', 'email', 'roles', 'new_email', 'new_cell_phone', 'new_extension', 'confirmed_at'])]
class BottinRequest extends Model
{
    public const MODIFICATION = 'modification';

    public const REMOVAL = 'retrait';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    protected function newCellPhone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CellPhone::format($value),
            set: fn (?string $value) => CellPhone::normalize($value),
        );
    }

    protected function newExtension(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => CellPhone::normalize($value));
    }
}
