<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Filament\Schemas\POProductForm;
use App\Services\Project\ProjectService;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;

use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Forms\Components as F;
use Filament\Tables\Columns as T;

class ProjectItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'projectItems';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::title();
    }

    public static function title(): string
    {
        return __('Products');
    }

    public function form(Schema $schema): Schema
    {
        return POProductForm::configure($schema)
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(fn(): string => __('Product'))
            ->columns([
                __index(),

                T\TextColumn::make('product.product_description')
                    ->label(__('Product'))
                    ->sortable(),

                T\TextColumn::make('qty')
                    ->numeric()
                    ->sortable(),

                T\TextColumn::make('unit_price')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),

                T\TextColumn::make('display_contract_price')
                    ->label(__('Contract price'))
                    ->money(fn($record) => $record->currency)
                    ->sortable(),

                T\TextColumn::make('value')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),
                T\TextColumn::make('contract_value')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                A\CreateAction::make()
                    ->after(function (): void {
                        $project = $this->getOwnerRecord();
                        app(ProjectService::class)->updateProjectInfo($project->id);
                    }),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->after(function (): void {
                        $project = $this->getOwnerRecord();
                        app(ProjectService::class)->updateProjectInfo($project->id);
                    }),
                A\DeleteAction::make()
                    ->after(function (): void {
                        $project = $this->getOwnerRecord();
                        app(ProjectService::class)->updateProjectInfo($project->id);
                    }),
            ])
            ->toolbarActions([]);
    }
}
