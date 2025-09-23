<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components as I;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // I\TextEntry::make('order_number')
                //     ->label(__('Order No.')),
                // I\TextEntry::make('order_date')
                //     ->label(__('Order Date'))
                //     ->date('d/m/Y'),
                // I\TextEntry::make('supplier.contact_name')
                //     ->label(__('Supplier')),
                // I\TextEntry::make('company.company_code')
                //     ->label(__('Company')),
            ]);
    }
}
