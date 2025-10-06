<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Pages\Dashboard\Actions\FilterAction;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm, HasFiltersAction;

    protected string $view = 'filament.pages.dashboard';

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->schema([
                    F\DatePicker::make('startDate'),
                    F\DatePicker::make('endDate'),
                ]),
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Section::make()
                    ->schema([
                        F\DatePicker::make('startDate'),
                        F\DatePicker::make('endDate'),
                        // ...
                    ])
                    ->columns(3),
            ]);
    }

}
