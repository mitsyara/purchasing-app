<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Validate date format
     */
    public static function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dateObj = Carbon::createFromFormat($format, $date);
        return $dateObj && $dateObj->format($format) === $date;
    }

    /**
     * Check if date is within range
     */
    public static function isDateInRange(string $date, string $startDate, string $endDate, string $format = 'Y-m-d'): bool
    {
        if (!self::isValidDate($date, $format) || !self::isValidDate($startDate, $format) || !self::isValidDate($endDate, $format)) {
            return false;
        }

        $checkDate = Carbon::createFromFormat($format, $date);
        $start = Carbon::createFromFormat($format, $startDate);
        $end = Carbon::createFromFormat($format, $endDate);

        return $checkDate->between($start, $end);
    }

    /**
     * Get business days between dates
     */
    public static function getBusinessDaysBetween(string $startDate, string $endDate, string $format = 'Y-m-d'): int
    {
        $start = Carbon::createFromFormat($format, $startDate);
        $end = Carbon::createFromFormat($format, $endDate);

        return $start->diffInWeekdays($end);
    }

    /**
     * Add business days to date
     */
    public static function addBusinessDays(string $date, int $days, string $format = 'Y-m-d'): string
    {
        $dateObj = Carbon::createFromFormat($format, $date);
        $dateObj->addWeekdays($days);

        return $dateObj->format($format);
    }

    /**
     * Format date for display
     */
    public static function formatForDisplay(string $date, string $inputFormat = 'Y-m-d', string $outputFormat = 'd/m/Y'): string
    {
        if (!self::isValidDate($date, $inputFormat)) {
            return $date;
        }

        return Carbon::createFromFormat($inputFormat, $date)->format($outputFormat);
    }

    /**
     * Get date range options for select
     */
    public static function getDateRangeOptions(): array
    {
        return [
            'today' => __('Today'),
            'yesterday' => __('Yesterday'),
            'this_week' => __('This Week'),
            'last_week' => __('Last Week'),
            'this_month' => __('This Month'),
            'last_month' => __('Last Month'),
            'this_quarter' => __('This Quarter'),
            'last_quarter' => __('Last Quarter'),
            'this_year' => __('This Year'),
            'last_year' => __('Last Year'),
            'custom' => __('Custom Range'),
        ];
    }

    /**
     * Convert date range option to actual dates
     */
    public static function convertDateRangeOption(string $option): array
    {
        $now = Carbon::now();

        return match ($option) {
            'today' => [
                'start' => $now->format('Y-m-d'),
                'end' => $now->format('Y-m-d'),
            ],
            'yesterday' => [
                'start' => $now->subDay()->format('Y-m-d'),
                'end' => $now->format('Y-m-d'),
            ],
            'this_week' => [
                'start' => $now->startOfWeek()->format('Y-m-d'),
                'end' => $now->endOfWeek()->format('Y-m-d'),
            ],
            'last_week' => [
                'start' => $now->subWeek()->startOfWeek()->format('Y-m-d'),
                'end' => $now->endOfWeek()->format('Y-m-d'),
            ],
            'this_month' => [
                'start' => $now->startOfMonth()->format('Y-m-d'),
                'end' => $now->endOfMonth()->format('Y-m-d'),
            ],
            'last_month' => [
                'start' => $now->subMonth()->startOfMonth()->format('Y-m-d'),
                'end' => $now->endOfMonth()->format('Y-m-d'),
            ],
            'this_quarter' => [
                'start' => $now->startOfQuarter()->format('Y-m-d'),
                'end' => $now->endOfQuarter()->format('Y-m-d'),
            ],
            'last_quarter' => [
                'start' => $now->subQuarter()->startOfQuarter()->format('Y-m-d'),
                'end' => $now->endOfQuarter()->format('Y-m-d'),
            ],
            'this_year' => [
                'start' => $now->startOfYear()->format('Y-m-d'),
                'end' => $now->endOfYear()->format('Y-m-d'),
            ],
            'last_year' => [
                'start' => $now->subYear()->startOfYear()->format('Y-m-d'),
                'end' => $now->endOfYear()->format('Y-m-d'),
            ],
            default => [
                'start' => null,
                'end' => null,
            ],
        };
    }
}