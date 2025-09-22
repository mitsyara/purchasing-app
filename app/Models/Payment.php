<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'purchase_shipment_id',

        'company_id',
        // real supplier
        'supplier_id',
        // contract supplier
        'supplier_contract_id',
        // money receiver
        'supplier_payment_id',

        'payment_method',
        'payment_date',
        'status',
        'amount',
        'currency',
        'exchange_rate',

        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'payment_date' => 'date',
    ];

    public function purchaseShipment(): BelongsTo
    {
        return $this->belongsTo(PurchaseShipment::class, 'purchase_shipment_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function supplierContract(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_contract_id');
    }

    public function supplierPayment(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_payment_id');
    }
}
