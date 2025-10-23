<?php

namespace App\Models;

use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Contact extends Model
{
    use \App\Traits\HasLoggedActivity;
    use HasComments;

    protected $fillable = [
        'is_mfg',
        'is_cus',
        'is_trader',
        'is_fav',
        'contact_name',
        'contact_code',
        'contact_short_name',
        'country_id',
        'region',
        'tax_code',
        'office_address',
        'office_email',
        'office_phone',
        'rep_title',
        'rep_gender',
        'rep_name',
        'warehouse_addresses',
        'bank_infos',
        'other_infos',
        'gmp_no',
        'gmp_expires_at',
        'certificates',
        'notes',
    ];

    protected $casts = [
        'region' => \App\Enums\RegionEnum::class,
        'rep_gender' => \App\Enums\ContactGenderEnum::class,

        'is_mfg' => 'boolean',
        'is_cus' => 'boolean',
        'is_trader' => 'boolean',
        'is_fav' => 'boolean',

        'warehouse_addresses' => 'array',
        'bank_infos' => 'array',
        'other_infos' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    // Manufacturer Products
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'mfg_id');
    }

    // Trader Strong Products
    public function strongProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_trader',
            'contact_id',
            'product_id'
        )
            ->withPivot(['id']);
    }
    public function traderStrongProducts(): HasMany
    {
        return $this->hasMany(ProductTrader::class, 'contact_id');
    }

    // Customer Strong Products
    public function buyerStrongProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_customer',
            'contact_id',
            'product_id'
        )
            ->withPivot(['id']);
    }
    public function customerStrongProducts(): HasMany
    {
        return $this->hasMany(ProductCustomer::class, 'contact_id');
    }

    // Staff in charge
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'contact_user', 'contact_id', 'user_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    // public function salesOrders(): HasMany
    // {
    //     return $this->hasMany(SalesOrder::class, 'contact_id');
    // }

    // Attributes
    public function contactName(): Attribute
    {
        return Attribute::make(
            get: fn($value) => mb_strtoupper($value),
            set: fn($value) => mb_strtoupper($value),
        );
    }
    public function contactCode(): Attribute
    {
        return Attribute::make(
            get: fn($value) => mb_strtoupper($value),
            set: fn($value) => mb_strtoupper($value),
        );
    }
    public function contactShortName(): Attribute
    {
        return Attribute::make(
            get: fn($value) => mb_strtoupper($value),
            set: fn($value) => mb_strtoupper($value),
        );
    }

    public function contactInfo(): Attribute
    {
        return Attribute::get(fn(): array
        => [
            $this->office_email,
            $this->office_phone,
        ]);
    }

    public function repInfo(): Attribute
    {
        return Attribute::get(fn(): string
        => "{$this->rep_gender?->getLabel()} {$this->rep_name}");
    }

    public function companyTypes(): Attribute
    {
        return Attribute::get(fn(): array => collect([
            $this->is_trader ? 'TRD' : null,
            $this->is_mfg ? 'MFG' : null,
            $this->is_cus ? 'CUS' : null,
        ])->filter()->values()->all());
    }
}
