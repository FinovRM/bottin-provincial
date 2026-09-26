<?php

namespace App\Models;

use App\Support\CellPhone;
use Database\Factories\ResponsableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person in charge of an organization's bottin. One person (one courriel)
 * may be responsable of several organizations: their name and phone are the
 * same everywhere.
 */
#[Fillable(['organization_id', 'name', 'email', 'cell_phone', 'extension'])]
class Responsable extends Model
{
    /** @use HasFactory<ResponsableFactory> */
    use HasFactory;

    /**
     * The member role reserved to responsables: it lives only in this table,
     * never among an organization's members or the roles it allows.
     */
    public const ROLE = 'Responsable du bottin';

    protected static function booted(): void
    {
        // A person already responsable elsewhere keeps the coordinates on file.
        static::creating(function (Responsable $responsable) {
            $existing = static::where('email', $responsable->email)->first();

            if ($existing) {
                $responsable->forceFill($existing->only(['name']) + [
                    'cell_phone' => $existing->getRawOriginal('cell_phone'),
                    'extension' => $existing->extension,
                ]);
            }
        });

        // Their coordinates follow them in every organization they're in charge of.
        static::updated(function (Responsable $responsable) {
            if (! $responsable->wasChanged(['name', 'email', 'cell_phone', 'extension'])) {
                return;
            }

            static::where('email', $responsable->getOriginal('email'))
                ->whereKeyNot($responsable->id)
                ->update(array_intersect_key(
                    $responsable->getAttributes(),
                    array_flip(['name', 'email', 'cell_phone', 'extension'])
                ));
        });
    }

    /**
     * Whether this name is the role reserved to responsables, however it's typed.
     */
    public static function isReservedRole(string $role): bool
    {
        return mb_strtolower(trim($role)) === mb_strtolower(self::ROLE);
    }

    /**
     * Stored as digits only, presented as "(xxx) xxx-xxxx".
     */
    protected function cellPhone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CellPhone::format($value),
            set: fn (?string $value) => CellPhone::normalize($value),
        );
    }

    /**
     * Stored as digits only.
     */
    protected function extension(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => CellPhone::normalize($value),
        );
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
