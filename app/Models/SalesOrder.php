<?php

namespace App\Models;

use App\Traits\HasPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasPayment;
    use \App\Traits\HasLoggedActivity;

    protected $fillable = [
        'order_status',
        'order_date',
        'order_number',
        'order_description',

        // seller
        'company_id',
        // customer
        'customer_id',
        // contract customer
        'customer_contract_id',
        // money receiver
        'customer_payment_id',

        'export_warehouse_id',
        'export_port_id',

        'staff_sales_id',
        'staff_approved_id',

        'etd_min',
        'etd_max',
        'eta_min',
        'eta_max',

        'is_skip_invoice',
        'currency',

        'pay_term_delay_at',
        'pay_term_days',
        'payment_method',

        'total_value',
        'total_contract_value',

        'extra_costs',
        'total_extra_cost',

        'total_received_value',
        'total_paid_value',

        'notes',

        'created_by',
        'updated_by',
        'shipment_index',
    ];

    protected $casts = [
        'order_date' => 'date',
        'order_status' => \App\Enums\OrderStatusEnum::class,
        'pay_term_delay_at' => \App\Enums\PaytermDelayAtEnum::class,
        'real_amount' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'total_received_value' => 'decimal:6',
        'total_paid_value' => 'decimal:6',
        'is_skip_invoice' => 'boolean',
        'pay_term_days' => 'integer',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'export_warehouse_id');
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'export_port_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function customerContract(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_contract_id');
    }

    public function customerPayment(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_payment_id');
    }

    public function staffSales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_sales_id');
    }

    public function staffApproved(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_approved_id');
    }

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id');
    }

    public function deliverySchedules(): HasMany
    {
        return $this->hasMany(SalesDeliverySchedule::class, 'sales_order_id');
    }

    // Edit history
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
