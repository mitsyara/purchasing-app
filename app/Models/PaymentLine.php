<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLine extends Model
{
    use \App\Traits\HasLoggedActivity;
    protected $fillable = [
        'payment_id',
        'payment_date',
        'amount',
        'currency',
        'exchange_rate',
        'note',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
        'amount_vnd' => 'decimal:3',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
