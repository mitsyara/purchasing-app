<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'company_code',
        'company_name',
        'company_address',
        'company_email',
        'company_phone',

        'company_tax_id',
        'country_id',

        'company_owner_gender',
        'company_owner',
        'company_owner_title',

        'company_website',
        'company_logo',
        'company_color',
        'company_bank_accounts',
        'company_currency',
        'company_language',
    ];

    protected $casts = [
        'company_bank_accounts' => 'array',
        'company_owner_gender' => \App\Enums\ContactGenderEnum::class,
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    // Staffs
    public function staffs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user', 'company_id', 'user_id');
    }

    // Purchase Orders
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'company_id');
    }
}
