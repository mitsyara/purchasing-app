<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'order_status',
        'order_date',
        'order_number',

        'company_id',
        'supplier_id',
        '3rd_party_id',

        'import_warehouse_id',
        'import_port_id',

        'staff_buy_id',
        'staff_approved_id',

        'staff_docs_id',
        'staff_declarant_id',
        'staff_sales_id',

        'etd',
        'etd_min',
        'etd_max',
        'eta',
        'eta_min',
        'eta_max',

        'is_foreign',
        'is_skip_invoice',
        'incoterm',
        'currency',

        'pay_term_delay_at',
        'pay_term_days',

        'total_value',
        'total_contract_value',

        'extra_costs',
        'total_extra_cost',

        'total_received_value',
        'total_paid_value',

        'order_notes',

        'created_by',
        'updated_by',
        'shipment_index',
    ];

    protected $casts = [
        'order_date' => 'date',
        'order_status' => \App\Enums\OrderStatusEnum::class,
        'incoterm' => \App\Enums\IncotermEnum::class,
        'real_amount' => 'decimal:6',
        'total_amount' => 'decimal:6',
    ];

    // Model relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(Contact::class, '3rd_party_id');
    }

    public function importWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'import_warehouse_id');
    }

    public function importPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'import_port_id');
    }

    // Staff relationships
    public function staffBuy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_buy_id');
    }

    public function staffSales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_sales_id');
    }

    public function staffApproved(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_approved_id');
    }

    public function staffDocs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_docs_id');
    }

    public function staffDeclarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_declarant_id');
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
