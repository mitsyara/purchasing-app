<?php

namespace App\Services\Contact;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Service xử lý business logic cho Contact
 */
class ContactService
{
    /**
     * Tự động tạo contact code
     */
    private function generateContactCode(): string
    {
        $prefix = 'CT';
        $lastContact = Contact::where('contact_code', 'LIKE', $prefix . '%')
            ->orderBy('contact_code', 'desc')
            ->first();

        if (!$lastContact) {
            return $prefix . '0001';
        }

        $lastNumber = (int) substr($lastContact->contact_code, 2);
        $newNumber = $lastNumber + 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}