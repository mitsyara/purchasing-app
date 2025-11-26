<?php

namespace App\Filament\Resources\Roles\Helpers;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Unique;

/**
 * Form Helper - chỉ chứa Filament form schemas
 */
trait RoleFormHelper
{
    /**
     * Form schema cho Role
     */
    protected static function roleFormSchema(): array
    {
        return [
            S\Grid::make()
                ->schema([
                    S\Section::make()
                        ->schema([
                            F\TextInput::make('name')
                                ->label(__('filament-shield::filament-shield.field.name'))
                                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, callable $get) {
                                    return $rule->where('guard_name', $get('guard_name'));
                                })
                                ->required()
                                ->maxLength(255),
                            
                            F\TextInput::make('guard_name')
                                ->label(__('filament-shield::filament-shield.field.guard_name'))
                                ->default(Utils::getFilamentAuthGuard())
                                ->nullable()
                                ->maxLength(255),
                            
                            F\Toggle::make('select_all')
                                ->label(__('filament-shield::filament-shield.field.select_all.name'))
                                ->helperText(__('filament-shield::filament-shield.field.select_all.message'))
                                ->reactive()
                                ->afterStateUpdated(function (\Closure $set, \Closure $get, $state) {
                                    static::toggleEntitiesViaSelectAll($set, $get, $state);
                                })
                                ->dehydrated(false),
                        ])
                        ->columns([
                            'sm' => 2,
                            'lg' => 3,
                        ]),
                ])
                ->columnSpan([
                    'sm' => 2,
                    'lg' => 1
                ]),
            
            S\Section::make(__('filament-shield::filament-shield.section'))
                ->schema([
                    S\Tabs::make('Permissions')
                        ->tabs(static::getResourcePermissionTabs())
                        ->columnSpan('full'),
                ])
                ->columnSpan([
                    'sm' => 2,
                    'lg' => 2
                ]),
        ];
    }

    /**
     * Helper methods cho Shield permissions
     */
    protected static function toggleEntitiesViaSelectAll(\Closure $set, \Closure $get, $state): void
    {
        // Implementation cho select all toggle
        // Logic phức tạp của Shield
    }

    protected static function getResourcePermissionTabs(): array
    {
        // Return Shield permission tabs
        return [];
    }
}