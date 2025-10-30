<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as HasBelongsToThrough;

class ProjectShipmentItem extends Model
{
    use HasBelongsToThrough;

    protected $fillable = [
        'project_shipment_id',
        'product_id',
        'assortment_id',
        'qty',
        'unit_price',
        'contract_price',
        'average_cost',
        'currency',
        'exchange_rate',
        'display_contract_price',
        'value',
        'contract_value',
        'total_cost',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'contract_price' => 'decimal:3',
        'average_cost' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
        'display_contract_price' => 'decimal:3',
        'value' => 'decimal:6',
        'contract_value' => 'decimal:6',
        'total_cost' => 'decimal:6',
    ];

    // Relationships

    public function projectShipment(): BelongsTo
    {
        return $this->belongsTo(ProjectShipment::class, 'project_shipment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function project(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Project::class,
            ProjectShipment::class
        );
    }

    public function company(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Company::class,
            [ProjectShipment::class, Project::class]
        );
    }

    public function supplier(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            [ProjectShipment::class, Project::class],
            foreignKeyLookup: [
                Contact::class => 'supplier_id',
            ]
        );
    }

    public function supplierContract(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            [ProjectShipment::class, Project::class],
            foreignKeyLookup: [
                Contact::class => 'supplier_contract_id',
            ]
        );
    }

    public function supplierPayment(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            [ProjectShipment::class, Project::class],
            foreignKeyLookup: [
                Contact::class => 'supplier_payment_id',
            ]
        );
    }

    public function endUser(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            [ProjectShipment::class, Project::class],
            foreignKeyLookup: [
                Contact::class => 'end_user_id',
            ]
        );
    }

}
