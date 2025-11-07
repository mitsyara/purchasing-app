<?php

namespace App\Services\Core;

use Carbon\Carbon;

class ValidationService
{
    /**
     * Validate date format
     */
    public function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dateObj = Carbon::createFromFormat($format, $date);
        return $dateObj && $dateObj->format($format) === $date;
    }

    /**
     * Validate date range
     */
    public function validateDateRange(string $startDate, string $endDate, string $format = 'Y-m-d'): bool
    {
        if (!$this->validateDate($startDate, $format) || !$this->validateDate($endDate, $format)) {
            return false;
        }

        $start = Carbon::createFromFormat($format, $startDate);
        $end = Carbon::createFromFormat($format, $endDate);

        return $start->lte($end);
    }

    /**
     * Validate required fields
     */
    public function validateRequired(array $data, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "The {$field} field is required.";
            }
        }

        return $errors;
    }

    /**
     * Validate email format
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate numeric fields
     */
    public function validateNumeric(array $data, array $numericFields): array
    {
        $errors = [];

        foreach ($numericFields as $field) {
            if (isset($data[$field]) && !is_numeric($data[$field])) {
                $errors[$field] = "The {$field} field must be numeric.";
            }
        }

        return $errors;
    }

    /**
     * Validate positive numbers
     */
    public function validatePositive(array $data, array $positiveFields): array
    {
        $errors = [];

        foreach ($positiveFields as $field) {
            if (isset($data[$field]) && (float) $data[$field] < 0) {
                $errors[$field] = "The {$field} field must be positive.";
            }
        }

        return $errors;
    }

    /**
     * Validate string length
     */
    public function validateLength(array $data, array $lengthRules): array
    {
        $errors = [];

        foreach ($lengthRules as $field => $rules) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];
            $length = strlen($value);

            if (isset($rules['min']) && $length < $rules['min']) {
                $errors[$field] = "The {$field} field must be at least {$rules['min']} characters.";
            }

            if (isset($rules['max']) && $length > $rules['max']) {
                $errors[$field] = "The {$field} field may not be greater than {$rules['max']} characters.";
            }
        }

        return $errors;
    }

    /**
     * Sanitize input data
     */
    public function sanitizeInput(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return trim(strip_tags($value));
            }
            return $value;
        }, $data);
    }

    /**
     * Validate currency code
     */
    public function validateCurrencyCode(string $currency): bool
    {
        $validCurrencies = ['USD', 'EUR', 'VND', 'CNY', 'JPY', 'KRW'];
        return in_array(strtoupper($currency), $validCurrencies);
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phone): bool
    {
        // Basic phone number validation
        return preg_match('/^[\+]?[\d\s\-\(\)]+$/', $phone);
    }
}