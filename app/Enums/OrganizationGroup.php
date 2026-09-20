<?php

namespace App\Enums;

/**
 * Within a level, a parent organization splits its children into two
 * independent groups — e.g. "AHM Acton Vale" (Organisation) and
 * "Ligue Centre-du-Québec" (Ligue) are both Local, in different groups.
 * A member's visibility stays scoped to their own group.
 */
enum OrganizationGroup: string
{
    case Organisation = 'organisation';
    case Ligue = 'ligue';

    public function label(): string
    {
        return match ($this) {
            self::Organisation => 'Organisation',
            self::Ligue => 'Ligue',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Organisation => 'Organisations',
            self::Ligue => 'Ligues',
        };
    }
}
