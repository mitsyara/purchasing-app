<?php

namespace App\Providers;

use Filament\Schemas\Components\Text;
use Filament\Tables\Enums\RecordActionsPosition;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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

        // Custom Js and CSS
        // \Filament\Support\Facades\FilamentAsset::register([
        //     \Filament\Support\Assets\Js::make('custom-script', __DIR__ . '/../../resources/js/custom.js'),
        // ]);

        // Render Hook
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\Tables\View\TablesRenderHook::HEADER_BEFORE,
            fn(): string => view('table-defferloading-indicator')->render(),
            [
                \App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages\ManageCustomsData::class,
            ]
        );

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::PAGE_START,
            fn(): string => \Livewire\Livewire::mount('screen-lock-modal'),
        );

        // App PIN
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn() => Blade::render('<x-filament::badge size="xl" color="info">' . (string) \Illuminate\Support\Facades\Cache::get('app_pin', '1234') . '</x-filament::badge>')
        );

        // Configure Filament global settings

        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table) {
            $table
                ->maxSelectableRecords(1000)
                ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
                ->searchOnBlur()
                // ->searchOnChange()
                // ->persistSearchInSession()
                // ->persistColumnSearchesInSession()
                ->splitSearchTerms()
                ->reorderableColumns(false)
                ->paginated([10, 20, 50])
                ->defaultPaginationPageOption(20)
            ;
        }, isImportant: true);

        \Filament\Schemas\Components\Tabs::configureUsing(function (\Filament\Schemas\Components\Tabs $tabs) {
            $tabs->contained(false);
        }, isImportant: true);

        \Filament\Forms\Components\TextInput::configureUsing(function (\Filament\Forms\Components\TextInput $input) {
            $input->trim();
        }, isImportant: true);

        \Filament\Forms\Components\Repeater::configureUsing(function (\Filament\Forms\Components\Repeater $repeater) {
            $repeater
                ->reorderable(false)
                ->addActionLabel(__('Add'));
        }, isImportant: true);

        \Filament\Forms\Components\Select::configureUsing(function (\Filament\Forms\Components\Select $select) {
            $select
                ->optionsLimit(10);
        }, isImportant: true);

        \Filament\Actions\Action::configureUsing(function (\Filament\Actions\Action $action) {
            $action
                ->closeModalByClickingAway(fn(): bool => $action instanceof \Filament\Actions\ViewAction);
        }, isImportant: true);

        \Filament\Actions\BulkAction::configureUsing(function (\Filament\Actions\BulkAction $action) {
            $action
                ->chunkSelectedRecords(100)
                ->fetchSelectedRecords(false)
                ->deselectRecordsAfterCompletion(true)
                ->requiresConfirmation()
            ;
        }, isImportant: true);

        // Set Filament field's labels auto translate
        \Filament\Actions\Action::configureUsing(
            function (\Filament\Actions\Action $action): void {
                $action->translateLabel();
            },
            isImportant: true
        );
        \Filament\Actions\ActionGroup::configureUsing(
            function (\Filament\Actions\ActionGroup $actionGroup): void {
                $actionGroup->translateLabel();
            },
            isImportant: true
        );
        \Filament\Tables\Columns\Column::configureUsing(
            function (\Filament\Tables\Columns\Column $column): void {
                $column->translateLabel();
            },
            isImportant: true
        );
        \Filament\Tables\Filters\Filter::configureUsing(
            function (\Filament\Tables\Filters\Filter $filter): void {
                $filter->translateLabel();
            },
            isImportant: true
        );
        \Filament\Forms\Components\Field::configureUsing(
            function (\Filament\Forms\Components\Field $field): void {
                $field->translateLabel();
            },
            isImportant: true
        );
        \Filament\Infolists\Components\Entry::configureUsing(
            function (\Filament\Infolists\Components\Entry $entry): void {
                $entry->translateLabel();
            },
            isImportant: true
        );
    }
}
