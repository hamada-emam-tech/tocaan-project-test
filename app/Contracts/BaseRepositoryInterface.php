<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a record by ID.
     */
    public function find(int $id): ?Model;

    /**
     * Find a record by ID or fail.
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update a record.
     *
     * @param  Model  $model
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model;
}
