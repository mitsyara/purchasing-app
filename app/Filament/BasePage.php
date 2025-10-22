<?php

namespace App\Filament;

use Filament\Pages\Page as FilamentPage;

abstract class BasePage extends FilamentPage
{
    // Override Labels
    public static function getLabel(): string
    {
        $label = static::$navigationLabel ?? static::$title ?? str(static::class)
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
        return static::$navigationLabel ?? static::getLabel();
    }
}
