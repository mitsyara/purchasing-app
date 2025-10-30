<?php

namespace App\Models;

use App\Traits\HasCustomQueryBuilder;
use Illuminate\Database\Eloquent\Model;

class CustomsDataSummary extends Model
{
    use HasCustomQueryBuilder;

    protected $connection = 'mysql_customs_data';

    protected $fillable = [
        'importer',
        'customs_data_category_id',
        'import_date',
        'total_import',
        'total_qty',
        'total_value',
        'is_vett',
    ];

    protected $casts = [
        'import_date' => 'date',
        'total_import' => 'integer',
        'total_qty' => 'decimal:3',
        'total_value' => 'decimal:6',
        'is_vett' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(CustomsDataCategory::class, 'customs_data_category_id');
    }
}
