<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy([\App\Observers\InventoryTransactionObserver::class])]
class InventoryTransaction extends Model
{
    use \App\Traits\HasCustomRecursiveQueryBuilder;
    use \App\Traits\HasLoggedActivity;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',

        'sourceable_id',
        'sourceable_type',
        'parent_id',

        'transaction_type',
        'transaction_date',
        'qty',
        'lot_no',
        'mfg_date',
        'exp_date',

        'break_price',
        'io_price',
        'io_currency',

        'is_checked',
        'checked_by',
        'notes',
    ];

    protected $casts = [
        'transaction_type' => \App\Enums\InventoryTransactionTypeEnum::class,
        'transaction_date' => 'date',
        'mfg_date' => 'date',
        'exp_date' => 'date',
        'is_checked' => 'boolean',

        'qty' => 'decimal:3',
        'io_price' => 'decimal:3',
        'break_price' => 'decimal:3',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    // Helpers

    /**
     * Mark this transaction as checked.
     */
    public function checked(?int $userId = null): void
    {
        if ($this->is_checked) return;

        $this->update([
            'transaction_date' => today(),
            'is_checked' => true,
            'checked_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Unmark this transaction.
     */
    public function unchecked(): void
    {
        if (!$this->is_checked) return;

        $this->update([
            'transaction_date' => null,
            'is_checked' => false,
            'checked_by' => null,
        ]);
    }
}
