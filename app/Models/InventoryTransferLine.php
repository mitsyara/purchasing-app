<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransferLine extends Model
{
    use \App\Traits\HasInventoryTransactions;

    protected $fillable = [
        'inventory_transfer_id',
        'product_id',
        'lot_no',
        'mfg_date',
        'exp_date',
        'transfer_qty',
        'io_price', // default: VND
    ];
}
