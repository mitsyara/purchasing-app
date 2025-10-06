<?php

namespace App\Filament\Resources\PurchaseShipments\Schemas;

use App\Models\PurchaseOrder;
use App\Models\PurchaseShipment;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Infolists\Components as I;

class PurchaseShipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                S\Tabs::make(__('Shipment Details'))
                    ->tabs([
                        S\Tabs\Tab::make('Order/Shipment Info')
                            ->schema([
                                S\Fieldset::make('Order Info')
                                    ->relationship('purchaseOrder')
                                    ->schema([
                                        ...static::orderInfoFields(),
                                    ])
                                    ->columnSpanFull(),

                                S\Fieldset::make('Shipment Info')
                                    ->schema([
                                        ...static::shipmentInfoFields(),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        S\Tabs\Tab::make('Products Info')
                            ->schema(fn(?PurchaseShipment $record): array => [
                                S\Livewire::make(\App\Livewire\PurchaseShipmentProductTable::class, [
                                    'shipment' => $record,
                                ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->contained(false),
            ]);
    }

    public static function orderInfoFields(): array
    {
        return [
            I\TextEntry::make('company.company_code')
                ->label('Buyer'),

            S\Group::make([
                I\TextEntry::make('order_number')
                    ->label('Order Number'),

                I\TextEntry::make('order_date')
                    ->label('Order Date')
                    ->date('d/m/Y'),
            ])
                ->columns(),

            I\TextEntry::make('supplier.contact_name')
                ->label('Supplier'),

            I\TextEntry::make('total_value')
                ->label('Total Value')
                ->money(fn(PurchaseOrder $record) => $record->currency),

        ];
    }

    public static function shipmentInfoFields(): array
    {
        return [
            I\TextEntry::make('shipment_status')
                ->label('Shipment Status'),

            I\TextEntry::make('tracking_no')
                ->label('Tracking No'),

            S\Group::make([
                I\TextEntry::make('staffDocs.name')
                    ->label('Docs Staff'),
                I\TextEntry::make('staffDeclarant.name')
                    ->label('Declarant Staff'),
                I\TextEntry::make('staffDeclarantProcessing.name')
                    ->label('Declarant Processing Staff'),
            ])
                ->columns(3)
                ->columnSpanFull(),

            S\Group::make([
                I\TextEntry::make('etd')
                    ->label('ETD')
                    ->getStateUsing(fn(PurchaseShipment $record) => $record->getEtd('d/m/Y'))
                    ->color(fn(PurchaseShipment $record) => $record->getEtdColor()),

                I\TextEntry::make('atd')
                    ->label('ATD')
                    ->date('d/m/Y'),

                I\TextEntry::make('eta')
                    ->label('ETA')
                    ->getStateUsing(fn(PurchaseShipment $record) => $record->getEta('d/m/Y'))
                    ->color(fn(PurchaseShipment $record) => $record->getEtdColor()),

                I\TextEntry::make('ata')
                    ->label('ATA')
                    ->date('d/m/Y'),
            ])
                ->columns()
                ->columnSpanFull(),

        ];
    }

    public static function productFields(): I\RepeatableEntry
    {
        return I\RepeatableEntry::make('purchaseShipmentLines')
            ->schema([
                S\Flex::make([
                    I\TextEntry::make('product.product_full_name')
                        ->label('Product'),
                    I\TextEntry::make('qty')
                        ->label('Quantity')
                        ->numeric()
                        ->suffix(fn($record) => ' ' . $record->product?->product_uom)
                        ->grow(false),
                ])
                    ->from('sm'),
            ]);
    }
}
