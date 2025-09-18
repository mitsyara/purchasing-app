<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vat extends Model
{
    protected $fillable = [
        'vat_name',
        'vat_value',
        'vat_notes',
    ];
}
