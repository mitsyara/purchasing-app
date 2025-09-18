<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyUser extends Pivot
{
    public $incrementing = true;

    protected $fillable = [
        'company_id',
        'user_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
