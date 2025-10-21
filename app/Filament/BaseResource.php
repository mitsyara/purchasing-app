<?php

namespace App\Filament;

use Filament\Resources\Resource as FilamentResource;

abstract class BaseResource extends FilamentResource
{
    // Override Labels
    public static function getModelLabel(): string
    {
        $label = static::$modelLabel ?? str(static::getModel())
            ->classBasename()
            ->kebab()
            ->replace('-', ' ')
            ->headline()
            ->toString();
        return __($label);
    }

    // Override Navigation Label
    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::getModelLabel();
    }
}
