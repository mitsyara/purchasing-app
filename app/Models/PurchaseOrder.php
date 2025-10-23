<?php

namespace App\Models;

use App\Services\PurchaseOrder\ProcessingOrder;
use App\Traits\HasCustomQueryBuilder;
use App\Traits\HasPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PurchaseOrder extends Model
{
    use HasCustomQueryBuilder;
    use HasPayment;
    use \App\Traits\HasLoggedActivity;

    protected $fillable = [
        'order_status',
        'order_date',
        'order_number',
        // yyyy-mm-dd order_number [supplier_code]
        'order_description',

        // buyer
        'company_id',
        // supplier
        'supplier_id',
        // contract supplier
        'supplier_contract_id',
        // money receiver
        'supplier_payment_id',

        // CIF end_user
        'end_user_id',

        'import_warehouse_id',
        'import_port_id',

        'staff_buy_id',
        'staff_approved_id',
        'staff_sales_id',

        'staff_docs_id',
        'staff_declarant_id',
        'staff_declarant_processing_id',

        'etd_min',
        'etd_max',
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

        'notes',

        'created_by',
        'updated_by',
        'shipment_index',
    ];

    protected $casts = [
        'order_date' => 'date',
        'order_status' => \App\Enums\OrderStatusEnum::class,
        'pay_term_delay_at' => \App\Enums\PaytermDelayAtEnum::class,
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

    public function staffDeclarantProcessing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_declarant_processing_id');
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

    // Product lines
    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id');
    }

    // Shipments
    public function purchaseShipments(): HasMany
    {
        return $this->hasMany(PurchaseShipment::class, 'purchase_order_id');
    }

    public function purchaseShipmentLines(): HasManyThrough
    {
        return $this->hasManyThrough(
            PurchaseShipmentLine::class,
            PurchaseShipment::class,
            'purchase_order_id',
            'purchase_shipment_id'
        );
    }

    // Helpers

    public function syncOrderInfo(): void
    {
        new \App\Services\PurchaseOrder\CallAllPurchaseOrderServices($this);
    }

    public function processOrder(array $data): bool
    {
        $supplierCode = $this->supplier->contact_short_name
            ?? $this->supplier->contact_code
            ?? 'N/A';

        $this->validateOrderData($data);

        return $this->update([
            'order_status' => \App\Enums\OrderStatusEnum::Inprogress,
            'order_number' => $data['order_number'],
            'order_date' => $data['order_date'],
            'order_description' => $data['order_date'] . ' ' . $data['order_number'] . ' [' . $supplierCode . ']',
        ]);
    }
    // validate date
    public function validateOrderData(array $data, ?string $format = 'Y-m-d'): void
    {
        if (!$data['order_number'] || !$data['order_date']) {
            throw new \Exception('Order number and order date are required.');
        }

        $date = \Carbon\Carbon::createFromFormat($format, $data['order_date']);

        if (!$date || $date->format($format) !== $data['order_date']) {
            throw new \Exception('Invalid order date format. Expected format: ' . $format);
        }
    }

    public function cancelOrder(): bool
    {
        return $this->update([
            'order_status' => \App\Enums\OrderStatusEnum::Canceled,
        ]);
    }

    // auto assign order number
    public function generateOrderNumber(?array $data = null): string
    {
        $id = str_pad($data['id'] ?? $this->company_id, 2, '0', STR_PAD_LEFT);
        $date = \Carbon\Carbon::parse($data['order_date'] ?? $this->order_date)->format('ymd');
        $base = "PO-{$id}{$date}";

        for (
            $i = 0;
            self::when($this->id, fn($q) => $q->where('id', '!=', $this->id))
                ->where('order_number', $order = $base . ($i ? sprintf('.%02d', $i) : ''))
                ->exists();
            $i++
        );

        return $order;
    }
}
