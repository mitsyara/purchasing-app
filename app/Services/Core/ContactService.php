<?php

namespace App\Services\Core;

use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

class ContactService
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository
    ) {}

    /**
     * Create a new contact
     */
    public function create(array $data): Contact
    {
        return $this->contactRepository->create($data);
    }

    /**
     * Update contact
     */
    public function update(int $id, array $data): bool
    {
        return $this->contactRepository->update($id, $data);
    }

    /**
     * Get all suppliers
     */
    public function getSuppliers(): Collection
    {
        return $this->contactRepository->findSuppliers();
    }

    /**
     * Get all customers
     */
    public function getCustomers(): Collection
    {
        return $this->contactRepository->findCustomers();
    }

    /**
     * Get all traders
     */
    public function getTraders(): Collection
    {
        return $this->contactRepository->findTraders();
    }

    /**
     * Search contacts
     */
    public function search(array $criteria): Collection
    {
        return $this->contactRepository->search($criteria);
    }

    /**
     * Find contact by code
     */
    public function findByCode(string $code): ?Contact
    {
        return $this->contactRepository->findByCode($code);
    }

    /**
     * Check if contact code exists
     */
    public function contactCodeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->contactRepository->contactCodeExists($code, $excludeId);
    }

    /**
     * Validate contact data
     */
    public function validateContactData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Check required fields
        if (empty($data['contact_name'])) {
            $errors['contact_name'] = 'Contact name is required.';
        }

        // Check contact code uniqueness if provided
        if (!empty($data['contact_code'])) {
            if ($this->contactCodeExists($data['contact_code'], $excludeId)) {
                $errors['contact_code'] = 'Contact code already exists.';
            }
        }

        // Validate email format if provided
        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Invalid email format.';
        }

        return $errors;
    }

    /**
     * Generate contact code
     */
    public function generateContactCode(string $contactName): string
    {
        // Remove special characters and spaces
        $code = preg_replace('/[^A-Za-z0-9]/', '', $contactName);
        $code = strtoupper(substr($code, 0, 6));

        // Make sure it's unique
        $originalCode = $code;
        $counter = 1;
        
        while ($this->contactCodeExists($code)) {
            $code = $originalCode . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Get contacts by country
     */
    public function getContactsByCountry(int $countryId): Collection
    {
        return $this->contactRepository->findByCountry($countryId);
    }

    /**
     * Get active contacts
     */
    public function getActiveContacts(): Collection
    {
        return $this->contactRepository->getActive();
    }
}