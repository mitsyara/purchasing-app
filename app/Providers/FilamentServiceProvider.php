<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table) {
            $table
                ->searchOnBlur()
                ->persistSearchInSession()
                ->persistColumnSearchesInSession()
                ->splitSearchTerms()
                ->reorderableColumns()
            ;
        });

        \Filament\Forms\Components\Repeater::configureUsing(function (\Filament\Forms\Components\Repeater $repeater) {
            $repeater
                ->reorderable(false)
                ->addActionLabel(__('Add'));
        });
    }
}
