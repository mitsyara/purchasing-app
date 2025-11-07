<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ContactRepository extends BaseRepository implements ContactRepositoryInterface
{
    /**
     * Get the model class name
     */
    protected function getModelClass(): string
    {
        return Contact::class;
    }

    /**
     * Find suppliers
     */
    public function findSuppliers(): Collection
    {
        return $this->model->where('is_trader', true)->whereNotNull('contact_name')->get();
    }

    /**
     * Find customers
     */
    public function findCustomers(): Collection
    {
        return $this->model->where('is_cus', true)->whereNotNull('contact_name')->get();
    }

    /**
     * Find traders
     */
    public function findTraders(): Collection
    {
        return $this->model->where('is_trader', true)->whereNotNull('contact_name')->get();
    }

    /**
     * Find by contact name
     */
    public function findByName(string $name): Collection
    {
        return $this->model->where('contact_name', 'like', "%{$name}%")->get();
    }

    /**
     * Find by contact code
     */
    public function findByCode(string $code): ?Contact
    {
        return $this->model->where('contact_code', $code)->first();
    }

    /**
     * Check if contact code exists
     */
    public function contactCodeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('contact_code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Search contacts by multiple criteria
     */
    public function search(array $criteria): Collection
    {
        $query = $this->getModel()->newQuery();

        if (isset($criteria['name'])) {
            $query->where('contact_name', 'like', "%{$criteria['name']}%");
        }

        if (isset($criteria['code'])) {
            $query->where('contact_code', 'like', "%{$criteria['code']}%");
        }

        if (isset($criteria['is_trader'])) {
            $query->where('is_trader', $criteria['is_trader']);
        }

        if (isset($criteria['is_cus'])) {
            $query->where('is_cus', $criteria['is_cus']);
        }

        if (isset($criteria['country_id'])) {
            $query->where('country_id', $criteria['country_id']);
        }

        return $query->get();
    }

    /**
     * Get active contacts
     */
    public function getActive(): Collection
    {
        return $this->model->whereNotNull('contact_name')->get();
    }

    /**
     * Get contacts by country
     */
    public function findByCountry(int $countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }
}