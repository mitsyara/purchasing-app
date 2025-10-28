<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as HasBelongsToThrough;

class ProjectShipment extends Model
{
    use HasBelongsToThrough;

    protected $fillable = [
        'project_id',
        'port_id',
        'currency',
        'staff_docs_id',
        'staff_declarant_id',
        'staff_sales_id',
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
        'total_contract_value',
        'extra_costs',
        'total_extra_cost',
        'average_cost',
        'display_total_contract_value',
        'notes',
    ];

    protected $casts = [
        'etd_min' => 'date',
        'etd_max' => 'date',
        'eta_min' => 'date',
        'eta_max' => 'date',
        'atd' => 'date',
        'ata' => 'date',

        'customs_declaration_date' => 'date',
        'customs_clearance_date' => 'date',

        'is_exchange_rate_final' => 'boolean',
        'extra_costs' => 'array',

        'average_cost' => 'decimal:3',
        'exchange_rate' => 'decimal:3',
        'total_value' => 'decimal:6',
        'total_contract_value' => 'decimal:6',
        'total_extra_cost' => 'decimal:6',
        'display_total_contract_value' => 'decimal:6',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function company(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Company::class,
            Project::class,
        );
    }

    public function supplier(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            Project::class,
            foreignKeyLookup: [Contact::class => 'supplier_id'],
        );
    }

    public function supplierContract(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            Project::class,
            foreignKeyLookup: [Contact::class => 'supplier_contract_id']
        );
    }

    public function supplierPayment(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            Project::class,
            foreignKeyLookup: [Contact::class => 'supplier_payment_id']
        );
    }

    public function endUser(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            Project::class,
            foreignKeyLookup: [Contact::class => 'end_user_id']
        );
    }
}
