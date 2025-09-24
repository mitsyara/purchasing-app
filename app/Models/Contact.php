<?php

namespace App\Models;

use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contact extends Model
{
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
        'attachment_files',
        'attachment_files_name',
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

    // Staff in charge
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'contact_user', 'contact_id', 'user_id')
            ->withPivot(['id']);
    }

    // Attributes

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
            $this->is_trader ? 'Trader' : null,
            $this->is_mfg ? 'Manufacturer' : null,
            $this->is_cus ? 'Customer' : null,
        ])->filter()->values()->all());
    }
}
