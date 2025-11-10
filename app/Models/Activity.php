<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends \Spatie\Activitylog\Models\Activity
{
    protected $primaryKey = 'id';

    protected $casts = [
        'properties' => 'json',
    ];
}
