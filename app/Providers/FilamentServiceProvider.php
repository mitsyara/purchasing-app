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
        $timezone = config('app.timezone', 'UTC');
        \Filament\Support\Facades\FilamentTimezone::set($timezone);

        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table) {
            $table
                ->maxSelectableRecords(1000)
                ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
                ->searchOnBlur()
                ->persistSearchInSession()
                ->persistColumnSearchesInSession()
                ->splitSearchTerms()
                ->reorderableColumns(false)
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

        \Filament\Actions\Action::configureUsing(function (\Filament\Actions\Action $action) {
            $action
                ->closeModalByClickingAway(fn(): bool => $action instanceof \Filament\Actions\ViewAction);
        });

        \Filament\Actions\BulkAction::configureUsing(function (\Filament\Actions\BulkAction $action) {
            $action
                ->chunkSelectedRecords(100)
                ->fetchSelectedRecords(false)
                ->deselectRecordsAfterCompletion(true)
                ->requiresConfirmation()
            ;
        });
    }
}
