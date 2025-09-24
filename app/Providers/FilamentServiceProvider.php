<?php

namespace App\Providers;

use Filament\Tables\Enums\RecordActionsPosition;
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
                ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
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

        \Filament\Forms\Components\Select::configureUsing(function (\Filament\Forms\Components\Select $select) {
            $select
                ->optionsLimit(10);
        });
    }
}
