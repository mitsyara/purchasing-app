<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Builder as RecursiveBuilder;

trait HasCustomRecursiveQueryBuilder
{
    /**
     * ancestors(): The model's recursive parents.
     * ancestorsAndSelf(): The model's recursive parents and itself.
     * bloodline(): The model's ancestors, descendants and itself.
     * children(): The model's direct children.
     * childrenAndSelf(): The model's direct children and itself.
     * descendants(): The model's recursive children.
     * descendantsAndSelf(): The model's recursive children and itself.
     * parent(): The model's direct parent.
     * parentAndSelf(): The model's direct parent and itself.
     * rootAncestor(): The model's topmost parent.
     * rootAncestorOrSelf(): The model's topmost parent or itself.
     * siblings(): The parent's other children.
     * siblingsAndSelf(): All the parent's children.
     */

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

        $baseBuilder = new RecursiveBuilder($query);

        $customQueryClass = $this->resolveQueryClass();
        if ($customQueryClass) {
            return new $customQueryClass($query);
        }

        return $baseBuilder;
    }
}
