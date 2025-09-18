<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contact extends Model
{
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
        'attachment_files',
        'attachment_files_name',
        'notes',
    ];

    protected $casts = [
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
    public function traderStrongProducts(): HasMany
    {
        return $this->hasMany(ProductTrader::class, 'contact_id');
    }
    public function strongProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_trader', 'contact_id', 'product_id')
            ->withPivot([
                'id',
            ]);
    }

    // Comments
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Attributes

    public function companyTypes(): Attribute
    {
        return Attribute::get(fn() => collect([
            $this->is_mfg ? 'Manufacturer' : null,
            $this->is_cus ? 'Customer' : null,
            $this->is_trader ? 'Trader' : null,
        ])->filter()->values()->all());
    }
}
