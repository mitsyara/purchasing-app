<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as HasBelongsToThrough;

class ProjectItem extends Model
{
    use HasBelongsToThrough;

    protected $fillable = [
        'project_id',
        'assortment_id',
        'product_id',
        'qty',
        'unit_price',
        'contract_price',
        'currency',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'contract_price' => 'decimal:3',
        'display_contract_price' => 'decimal:3',
        'value' => 'decimal:6',
        'contract_value' => 'decimal:6',
    ];

    // Relationships

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

    public function assortment(): BelongsTo
    {
        return $this->belongsTo(Assortment::class, 'assortment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
