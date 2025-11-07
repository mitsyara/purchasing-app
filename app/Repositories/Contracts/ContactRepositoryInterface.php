<?php

namespace App\Repositories\Contracts;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

interface ContactRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find suppliers
     */
    public function findSuppliers(): Collection;

    /**
     * Find customers
     */
    public function findCustomers(): Collection;

    /**
     * Find traders
     */
    public function findTraders(): Collection;

    /**
     * Find by contact name
     */
    public function findByName(string $name): Collection;

    /**
     * Find by contact code
     */
    public function findByCode(string $code): ?Contact;

    /**
     * Check if contact code exists
     */
    public function contactCodeExists(string $code, ?int $excludeId = null): bool;

    /**
     * Search contacts by multiple criteria
     */
    public function search(array $criteria): Collection;

    /**
     * Get active contacts
     */
    public function getActive(): Collection;

    /**
     * Get contacts by country
     */
    public function findByCountry(int $countryId): Collection;
}