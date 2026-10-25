<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Service for resolving user timezones, formatting dates, and managing timezone lists.
 */
class TimezoneService
{
    /**
     * Cached timezone options array.
     *
     * @var array<string, string>|null
     */
    protected static ?array $cachedOptions = null;

    /**
     * Resolve the active timezone for the current user or guest request.
     */
    public static function getUserTimezone(?User $user = null): string
    {
        $validTimezones = timezone_identifiers_list();

        // 1. Explicit user model passed or authenticated user
        $targetUser = $user ?? auth()->user();
        if ($targetUser && ! empty($targetUser->timezone) && in_array($targetUser->timezone, $validTimezones, true)) {
            return $targetUser->timezone;
        }

        // 2. Cookie preference (e.g. for guest browsers or auto-detected sessions)
        if (request()->hasCookie('ctc_user_timezone')) {
            $cookieTz = request()->cookie('ctc_user_timezone');
            if ($cookieTz && in_array($cookieTz, $validTimezones, true)) {
                return $cookieTz;
            }
        }

        // 3. Fallback to application default timezone
        return config('app.user_timezone', config('app.timezone', 'UTC'));
    }

    /**
     * Convert a Carbon date instance or date string into the user's timezone.
     */
    public static function convert(CarbonInterface|string|null $date, ?string $timezone = null): ?Carbon
    {
        if (! $date) {
            return null;
        }

        $tz = $timezone ?: static::getUserTimezone();
        $carbon = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        return $carbon->setTimezone($tz);
    }

    /**
     * Format a date in the user's active timezone.
     */
    public static function format(CarbonInterface|string|null $date, string $format = 'M j, Y', ?string $timezone = null): string
    {
        if (! $date) {
            return '';
        }

        $converted = static::convert($date, $timezone);

        return $converted ? $converted->format($format) : '';
    }

    /**
     * Format a date and time with timezone abbreviation in the user's active timezone.
     */
    public static function formatDateTime(CarbonInterface|string|null $date, string $format = 'M j, Y H:i T', ?string $timezone = null): string
    {
        if (! $date) {
            return '';
        }

        $converted = static::convert($date, $timezone);

        return $converted ? $converted->format($format) : '';
    }

    /**
     * Return all available timezones formatted with their current UTC offset grouped by region for select dropdowns.
     *
     * @return array<string, array<string, string>>
     */
    public static function getTimezoneOptions(): array
    {
        if (static::$cachedOptions !== null) {
            return static::$cachedOptions;
        }

        $identifiers = timezone_identifiers_list();
        $now = Carbon::now('UTC');

        $regionOrder = [
            'UTC' => 'UTC / Universal',
            'America' => 'America',
            'Europe' => 'Europe',
            'Asia' => 'Asia',
            'Australia' => 'Australia',
            'Pacific' => 'Pacific',
            'Africa' => 'Africa',
            'Atlantic' => 'Atlantic',
            'Indian' => 'Indian',
            'Antarctica' => 'Antarctica',
            'Arctic' => 'Arctic',
        ];

        $grouped = [];
        foreach ($regionOrder as $label) {
            $grouped[$label] = [];
        }

        foreach ($identifiers as $tz) {
            try {
                $converted = $now->copy()->setTimezone($tz);
                $offset = $converted->format('P'); // e.g. +01:00 or -05:00
                $display = "{$tz} (UTC{$offset})";
            } catch (\Throwable) {
                $display = $tz;
            }

            if ($tz === 'UTC') {
                $grouped['UTC / Universal']['UTC'] = 'UTC (UTC+00:00)';

                continue;
            }

            $parts = explode('/', $tz, 2);
            $region = $parts[0] ?? 'Other';
            $groupLabel = $regionOrder[$region] ?? $region;

            if (! isset($grouped[$groupLabel])) {
                $grouped[$groupLabel] = [];
            }

            $grouped[$groupLabel][$tz] = $display;
        }

        // Filter out any empty groups
        $grouped = array_filter($grouped, fn (array $items): bool => ! empty($items));

        static::$cachedOptions = $grouped;

        return $grouped;
    }
}
