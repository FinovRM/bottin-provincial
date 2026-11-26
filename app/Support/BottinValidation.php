<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * The yearly check of each organization's bottin: on the control date (a day
 * and month), a validation made that day or during the acceptable delay (in
 * days) before it is on time. A later validation corrects the situation.
 */
class BottinValidation
{
    public const TIMEZONE = 'America/Toronto';

    /** First reminder the day after the control date, then daily from this many days after it. */
    public const DAILY_REMINDERS_AFTER_DAYS = 7;

    public static function controlDay(): ?int
    {
        $day = Setting::get('validation_control_day');

        return $day === null ? null : (int) $day;
    }

    public static function controlMonth(): ?int
    {
        $month = Setting::get('validation_control_month');

        return $month === null ? null : (int) $month;
    }

    public static function delayDays(): int
    {
        return (int) Setting::get('validation_delay_days', 0);
    }

    public static function isConfigured(): bool
    {
        return static::controlDay() !== null && static::controlMonth() !== null;
    }

    public static function today(): Carbon
    {
        return Carbon::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * The most recent control date on or before $day — or strictly before it.
     * None before the first control date following the configuration: the
     * check doesn't apply retroactively.
     */
    public static function latestControlDate(?Carbon $day = null, bool $strictlyBefore = false): ?Carbon
    {
        if (! static::isConfigured()) {
            return null;
        }

        $day = ($day ?? static::today())->copy()->startOfDay();
        $date = Carbon::create($day->year, static::controlMonth(), static::controlDay(), 0, 0, 0, self::TIMEZONE);

        if ($strictlyBefore ? $date->gte($day) : $date->gt($day)) {
            $date->subYear();
        }

        $configuredOn = Setting::get('validation_configured_on');

        return $configuredOn && $date->lt(Carbon::parse($configuredOn, self::TIMEZONE)) ? null : $date;
    }

    /**
     * From when a validation counts for the control date: that many days before it.
     */
    public static function windowStart(Carbon $controlDate): Carbon
    {
        return $controlDate->copy()->subDays(static::delayDays());
    }

    /**
     * Whether this organization's bottin is validated for the current control
     * date. Always true when no control date is set.
     */
    public static function isValid(Organization $organization, ?Carbon $day = null): bool
    {
        $controlDate = static::latestControlDate($day);

        if (! $controlDate) {
            return true;
        }

        return $organization->validated_at !== null
            && $organization->validated_at->gte(static::windowStart($controlDate));
    }

    /**
     * The control date whose reminders go out on $day, or null when none do:
     * the day after it, then every day from the 7th day after it.
     */
    public static function reminderControlDate(?Carbon $day = null): ?Carbon
    {
        $day = ($day ?? static::today())->copy()->startOfDay();
        $controlDate = static::latestControlDate($day, strictlyBefore: true);

        if (! $controlDate) {
            return null;
        }

        $daysAfter = (int) $controlDate->diffInDays($day);

        return $daysAfter === 1 || $daysAfter >= self::DAILY_REMINDERS_AFTER_DAYS ? $controlDate : null;
    }
}
