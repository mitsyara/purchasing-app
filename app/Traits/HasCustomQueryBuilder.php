<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasCustomQueryBuilder
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasCustomQueryBuilder
{
    protected function resolveQueryClass(): ?string
    {
        $modelClass = get_class($this);
        $shortName = class_basename($modelClass);
        $queryClass = "App\\Models\\Queries\\{$shortName}Query";

        return class_exists($queryClass) ? $queryClass : null;
    }

    public function newEloquentBuilder($query)
    {
        if (!($this instanceof Model)) {
            throw new \InvalidArgumentException(static::class . ' must be an instance of ' . Model::class);
        }
        $queryClass = $this->resolveQueryClass();
        return $queryClass ? new $queryClass($query) : new Builder($query);
    }
}
