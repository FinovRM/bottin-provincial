<?php

namespace App\Models;

use Database\Factories\PersonalFilterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['name', 'description', 'region_ids', 'local_ids', 'roles'])]
class PersonalFilter extends Model
{
    /** @use HasFactory<PersonalFilterFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'region_ids' => 'array',
            'local_ids' => 'array',
            'roles' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function filterable(): MorphTo
    {
        return $this->morphTo();
    }
}
