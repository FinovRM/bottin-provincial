<?php

namespace App\Enums;

enum OrganizationLevel: string
{
    case Provincial = 'provincial';
    case Regional = 'regional';
    case Local = 'local';

    public function label(): string
    {
        return match ($this) {
            self::Provincial => 'Provincial',
            self::Regional => 'Régional',
            self::Local => 'Local',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Provincial => 'Provinciales',
            self::Regional => 'Régionales',
            self::Local => 'Locales',
        };
    }

    /**
     * The level created by a responsable at this level, or null if this level cannot have children.
     */
    public function childLevel(): ?self
    {
        return match ($this) {
            self::Provincial => self::Regional,
            self::Regional => self::Local,
            self::Local => null,
        };
    }
}
