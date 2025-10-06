<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vat extends Model
{
    use \App\Traits\HasLoggedActivity;
    protected $fillable = [
        'vat_name',
        'vat_value',
        'notes',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'vat_id');
    }
}
