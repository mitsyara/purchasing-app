<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomsDataByImporter extends Model
{
    // Connection
    protected $connection = 'mysql_customs_data';
    public $timestamps = false;

    protected $fillable = [
        'importer',
        'total_import',
        'total_qty',
        'total_value',
        'import_month',
        'is_vett',
    ];

    protected $casts = [
        'is_vett' => 'boolean',
    ];
}
