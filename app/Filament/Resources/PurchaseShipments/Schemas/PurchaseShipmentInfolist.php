<?php

namespace App\Filament\Resources\PurchaseShipments\Schemas;

use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
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
                I\TextEntry::make('purchaseOrder.order_number')
                    ->label(__('Order No.'))
                    ->url(fn(PurchaseShipment $record): string
                    => ViewPurchaseOrder::getUrl(['record' => $record->purchase_order_id]), true)
                    ->color('info'),

                I\TextEntry::make('tracking_no')
                    ->label(__('Tracking No.')),

                I\TextEntry::make('shipment_status')
                    ->label(__('Shipment Status')),

                I\TextEntry::make('etd')->label('ETD')
                    ->getStateUsing(function ($record) {
                        if (
                            $record->etd_min
                            && $record->etd_max
                            && $record->etd_min !== $record->etd_max
                        ) {
                            return $record->etd_min->format('d/m/Y')
                                . ' ~ ' . $record->etd_max->format('d/m/Y');
                        }
                        if (
                            $record->etd_min
                            || $record->etd_min === $record->etd_max
                            || $record->etd_max
                        ) {
                            return $record->etd_min?->format('d/m/Y')
                                ?? $record->etd_max?->format('d/m/Y');
                        }
                    }),
                I\TextEntry::make('eta')->label('ETA')
                    ->getStateUsing(function ($record) {
                        if (
                            $record->eta_min
                            && $record->eta_max
                            && $record->eta_min !== $record->eta_max
                        ) {
                            return $record->eta_min->format('d/m/Y')
                                . ' ~ ' . $record->eta_max->format('d/m/Y');
                        }
                        if (
                            $record->eta_min
                            || $record->eta_min === $record->eta_max
                            || $record->eta_max
                        ) {
                            return $record->eta_min?->format('d/m/Y')
                                ?? $record->eta_max?->format('d/m/Y');
                        }
                    }),
                I\TextEntry::make('atd')->label('ATD')
                    ->date('d/m/Y'),
                I\TextEntry::make('ata')->label('ATA')
                    ->date('d/m/Y'),

                I\TextEntry::make('customs_declaration_no'),
                I\TextEntry::make('customs_declaration_date'),
                I\TextEntry::make('customs_clearance_status'),
                I\TextEntry::make('customs_clearance_date'),
            ]);
    }
}
