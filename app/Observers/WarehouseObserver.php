<?php

namespace App\Observers;

use App\Models\Warehouse;

class WarehouseObserver
{
    /**
     * Handle the Warehouse "created" event.
     */
    public function created(Warehouse $warehouse): void
    {
        if(!$warehouse->warehouse_code) {
            $warehouse->setWarehouseCode();
        }
    }

    /**
     * Handle the Warehouse "updated" event.
     */
    public function updated(Warehouse $warehouse): void
    {
        if(!$warehouse->warehouse_code) {
            $warehouse->setWarehouseCode();
        }
    }

    /**
     * Handle the Warehouse "deleted" event.
     */
    public function deleted(Warehouse $warehouse): void
    {
        //
    }

    /**
     * Handle the Warehouse "restored" event.
     */
    public function restored(Warehouse $warehouse): void
    {
        //
    }

    /**
     * Handle the Warehouse "force deleted" event.
     */
    public function forceDeleted(Warehouse $warehouse): void
    {
        //
    }
}
