<?php

namespace App\Traits;

use App\Models\Payment;
use App\Models\PaymentLine;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasPayment
{
    /**
     * Get all of the model's payments.
     */
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function paymentLines(): HasManyThrough
    {
        return $this->hasManyThrough(
            PaymentLine::class,
            Payment::class,
            'payable_id',
            'payment_id',
            'id',
            'id'
        )->where('payments.payable_type', self::class);
    }
}
