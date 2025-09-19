<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseShipment extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'port_id',
        'company_id',
        'warehouse_id',
        'currency',

        'staff_buy_id',
        'staff_docs_id',
        'staff_declarant_id',

        'tracking_no',
        'shipment_status',

        'etd_min',
        'etd_max',
        'eta_min',
        'eta_max',
        'atd',
        'ata',

        'customs_declaration_no',
        'customs_declaration_date',
        'customs_clearance_status',
        'customs_clearance_date',

        'exchange_rate',
        'is_exchange_rate_final',

        'total_value',
        'extra_costs',
        'total_extra_cost',
        'average_cost',

        'notes',
        'attachment_files',
        'attachment_files_name',
    ];

    protected $casts = [
        'etd_min' => 'date',
        'etd_max' => 'date',
        'eta_min' => 'date',
        'eta_max' => 'date',
        'atd' => 'date',
        'ata' => 'date',
        'customs_declaration_date' => 'date',

        'is_exchange_rate_final' => 'boolean',

        'attachment_files' => 'array',
        'attachment_files_name' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function staffBuy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_buy_id');
    }

    public function staffDocs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_docs_id');
    }

    public function staffDeclarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_declarant_id');
    }
}
