<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'payable_id',
        'payable_type',

        'company_id',
        'supplier_id',
        'supplier_contract_id',
        'supplier_payment_id',

        'payment_type', // 0: out, 1: in
        'payment_status',
        'due_date',
        'total_amount',
        'currency',
        'average_exchange_rate',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_status' => \App\Enums\PaymentStatusEnum::class,
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'average_exchange_rate' => 'decimal:4',
    ];

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

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
