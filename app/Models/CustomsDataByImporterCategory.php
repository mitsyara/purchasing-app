<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomsDataByImporterCategory extends Model
{
    //
    protected $connection = 'mysql_customs_data';
    public $timestamps = false;

    protected $fillable = [
        'importer',
        'customs_data_category_id',
        'total_import',
        'total_qty',
        'total_value',
        'is_vett',
    ];

    protected $casts = [
        'is_vett' => 'boolean',
    ];

    /**
     * Relation tới category (nullable)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CustomsDataCategory::class, 'customs_data_category_id')
            ->withDefault(['name' => __('Other')]);
    }
}
