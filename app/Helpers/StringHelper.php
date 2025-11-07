<?php

namespace App\Helpers;

class StringHelper
{
    /**
     * Generate slug from string
     */
    public static function slug(string $text, string $separator = '-'): string
    {
        $text = preg_replace('/[^\p{L}\p{Nd}]+/u', $separator, $text);
        $text = trim($text, $separator);
        
        return strtolower($text);
    }

    /**
     * Clean string for code generation
     */
    public static function cleanForCode(string $text, int $maxLength = 6): string
    {
        $text = preg_replace('/[^A-Za-z0-9]/', '', $text);
        
        return strtoupper(substr($text, 0, $maxLength));
    }

    /**
     * Format currency value
     */
    public static function formatCurrency(float $amount, string $currency = 'USD', int $decimals = 2): string
    {
        $formatted = number_format($amount, $decimals);
        
        return match (strtoupper($currency)) {
            'USD' => '$' . $formatted,
            'EUR' => '€' . $formatted,
            'VND' => $formatted . ' ₫',
            'CNY' => '¥' . $formatted,
            'JPY' => '¥' . $formatted,
            'KRW' => '₩' . $formatted,
            default => $formatted . ' ' . strtoupper($currency),
        };
    }

    /**
     * Parse currency value
     */
    public static function parseCurrency(string $currencyString): array
    {
        $patterns = [
            'USD' => '/^\$([0-9,]+\.?\d*)$/',
            'EUR' => '/^€([0-9,]+\.?\d*)$/',
            'VND' => '/^([0-9,]+\.?\d*)\s*₫$/',
            'CNY' => '/^¥([0-9,]+\.?\d*)$/',
            'JPY' => '/^¥([0-9,]+\.?\d*)$/',
            'KRW' => '/^₩([0-9,]+\.?\d*)$/',
        ];

        foreach ($patterns as $currency => $pattern) {
            if (preg_match($pattern, trim($currencyString), $matches)) {
                return [
                    'amount' => (float) str_replace(',', '', $matches[1]),
                    'currency' => $currency,
                ];
            }
        }

        // Fallback: try to extract number and assume currency from end
        if (preg_match('/([0-9,]+\.?\d*)\s*([A-Z]{3})$/', trim($currencyString), $matches)) {
            return [
                'amount' => (float) str_replace(',', '', $matches[1]),
                'currency' => $matches[2],
            ];
        }

        return [
            'amount' => 0,
            'currency' => 'USD',
        ];
    }

    /**
     * Format phone number
     */
    public static function formatPhone(string $phone, string $format = 'international'): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);
        
        return match ($format) {
            'international' => '+' . $digits,
            'national' => substr($digits, -10),
            'display' => substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6),
            default => $phone,
        };
    }

    /**
     * Truncate text with ellipsis
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Generate initials from name
     */
    public static function initials(string $name, int $limit = 2): string
    {
        $words = explode(' ', trim($name));
        $initials = '';

        for ($i = 0; $i < min(count($words), $limit); $i++) {
            $initials .= strtoupper(substr($words[$i], 0, 1));
        }

        return $initials;
    }

    /**
     * Convert camelCase to snake_case
     */
    public static function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * Convert snake_case to camelCase
     */
    public static function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Check if string contains any of the needles
     */
    public static function containsAny(string $haystack, array $needles, bool $caseInsensitive = true): bool
    {
        $haystack = $caseInsensitive ? strtolower($haystack) : $haystack;
        
        foreach ($needles as $needle) {
            $needle = $caseInsensitive ? strtolower($needle) : $needle;
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}