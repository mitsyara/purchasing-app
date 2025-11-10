<?php

namespace App\Traits\Filament;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\JsContent;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait cho các form fields chung của shipment
 */
trait HasShipmentFormFields
{
    /**
     * Trường thông tin cơ bản của shipment
     */
    public function shipmentBasicFields(): array
    {
        return [
            S\Flex::make([
                F\ToggleButtons::make('shipment_status')
                    ->label(__('Shipment Status'))
                    ->options(\App\Enums\ShipmentStatusEnum::class)
                    ->default(\App\Enums\ShipmentStatusEnum::Pending->value)
                    ->grouped()
                    ->grow(false)
                    ->disableOptionWhen(fn(string $value, string $operation): bool
                    => $operation === 'create'
                        && $value === \App\Enums\ShipmentStatusEnum::Cancelled->value)
                    ->columnSpanFull()
                    ->required(),

                F\TextInput::make('tracking_no')
                    ->label(__('Tracking Number')),
            ])
                ->from('lg')
                ->columnSpanFull(),
        ];
    }

    /**
     * Trường thông tin nhân viên
     */
    public function shipmentStaffFields(): array
    {
        return [
            S\Group::make([
                F\Select::make('staff_docs_id')
                    ->label(__('Docs Staff'))
                    ->relationship(
                        name: 'staffDocs',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->default(fn($livewire) => $livewire->getOwnerRecord()?->staff_docs_id)
                    ->required(),

                F\Select::make('staff_declarant_id')
                    ->relationship(
                        name: 'staffDeclarant',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->default(fn($livewire) => $livewire->getOwnerRecord()?->staff_declarant_id)
                    ->required(),

                F\Select::make('staff_declarant_processing_id')
                    ->relationship(
                        name: 'staffDeclarantProcessing',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->default(fn($livewire) => $livewire->getOwnerRecord()?->staff_declarant_processing_id)
                    ->required(),
            ])
                ->columns([
                    'default' => 2,
                    'lg' => 3,
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * Trường port và warehouse
     */
    public function shipmentLocationFields(): array
    {
        return [
            F\Select::make('port_id')
                ->label(__('Port'))
                ->relationship(
                    name: 'port',
                    titleAttribute: 'port_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query
                )
                ->default(fn($livewire) => $livewire->getOwnerRecord()?->import_port_id)
                ->required(function ($livewire): bool {
                    $order = $livewire->getOwnerRecord();
                    return !$order?->is_skip_invoice && $order?->is_foreign;
                }),

            F\Select::make('warehouse_id')
                ->label(__('Warehouse'))
                ->relationship(
                    name: 'warehouse',
                    titleAttribute: 'warehouse_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query
                )
                ->default(fn($livewire) => $livewire->getOwnerRecord()?->import_warehouse_id)
                ->required(),
        ];
    }

    /**
     * Trường ETD/ETA fields sử dụng helper function
     */
    public function etdEtaFields(bool $isRequired = true): array
    {
        return __eta_etd_fields($isRequired, true);
    }

    /**
     * Trường ATD/ATA fields sử dụng helper function
     */
    public function atdAtaFields(int|array|null $columns = 2): \Filament\Schemas\Components\Group
    {
        return __atd_ata_fields($columns);
    }

    /**
     * Trường extra costs và notes
     */
    public function costsAndNotesFields(): array
    {
        return [
            S\Fieldset::make(__('Extra Costs'))
                ->schema([
                    F\Repeater::make('extra_costs')
                        ->label(__('Extra Costs'))
                        ->simple(F\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->required())
                        ->defaultItems(0)
                        ->grid(3)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            __notes()
                ->rows(5),
        ];
    }

    /**
     * Headers cho repeater table của shipment lines
     */
    public function shipmentLinesTableHeaders(): array
    {
        return [
            F\Repeater\TableColumn::make('Product')
                ->markAsRequired()
                ->width('300px'),
            F\Repeater\TableColumn::make('Qty')
                ->markAsRequired()
                ->width('120px'),
            F\Repeater\TableColumn::make('Unit Price')
                ->markAsRequired()
                ->width('150px'),
            F\Repeater\TableColumn::make('Contract Price')
                ->width('150px'),
        ];
    }
}
