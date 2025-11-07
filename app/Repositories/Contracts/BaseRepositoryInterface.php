<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records
     */
    public function all(): Collection;

    /**
     * Find record by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find record by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Create new record
     */
    public function create(array $data): Model;

    /**
     * Update record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete record
     */
    public function delete(int $id): bool;

    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find records by criteria
     */
    public function where(string $column, $value): Collection;

    /**
     * Count records
     */
    public function count(): int;

    /**
     * Check if record exists
     */
    public function exists(int $id): bool;
}