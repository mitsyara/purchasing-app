<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Port extends Model
{
    protected $fillable = [
        'port_code',
        'port_name',
        'port_address',
        'country_id',
        'region',
        'port_type',
        'phones',
        'emails',
        'website',
    ];

    protected $casts = [
        'port_type' => \App\Enums\PortTypeEnum::class,
        'region' => \App\Enums\RegionEnum::class,
        'phones' => 'array',
        'emails' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
