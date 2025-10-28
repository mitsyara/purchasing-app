<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    protected $fillable = [
        'project_status',
        'project_date',
        'project_number',
        'project_description',

        'company_id',
        'import_port_id',

        'supplier_id',
        'supplier_contract_id',
        'supplier_payment_id',
        'end_user_id',

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

        'is_cif',
        'is_foreign',
        'is_skip_invoice',
        'incoterm',
        'currency',
        'payment_method',
        'pay_term_delay_at',
        'pay_term_days',

        'import_extra_costs',
        'import_total_value',
        'import_total_contract_value',
        'import_total_extra_cost',
        'import_total_received_value',
        'import_total_paid_value',
        'export_extra_costs',
        'export_total_value',
        'export_total_contract_value',
        'export_total_extra_cost',
        'export_total_received_value',
        'export_total_paid_value',

        'shipping_address',
        'billing_address',
        'notes',
        'created_by',
        'updated_by',
        'shipment_index',
    ];

    protected $casts = [
        'project_date' => 'date',
        'is_cif' => 'boolean',
        'is_foreign' => 'boolean',
        'is_skip_invoice' => 'boolean',
        'etd_min' => 'date',
        'etd_max' => 'date',
        'eta_min' => 'date',
        'eta_max' => 'date',
        'pay_term_delay_at' => \App\Enums\PaytermDelayAtEnum::class,

        'import_extra_costs' => 'array',
        'export_extra_costs' => 'array',
        'import_total_value' => 'decimal:6',
        'import_total_contract_value' => 'decimal:6',
        'import_total_extra_cost' => 'decimal:6',
        'import_total_received_value' => 'decimal:6',
        'import_total_paid_value' => 'decimal:6',
        'export_total_value' => 'decimal:6',
        'export_total_contract_value' => 'decimal:6',
        'export_total_extra_cost' => 'decimal:6',
        'export_total_received_value' => 'decimal:6',
        'export_total_paid_value' => 'decimal:6',
    ];

    // Relationships

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

    public function endUser(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'end_user_id');
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function projectItems(): HasMany
    {
        return $this->hasMany(ProjectItem::class, 'project_id');
    }

    public function projectShipments(): HasMany
    {
        return $this->hasMany(ProjectShipment::class, 'project_id');
    }

    public function projectShipmentItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProjectShipmentItem::class,
            ProjectShipment::class,
            'project_id',
            'shipment_id'
        );
    }
}
