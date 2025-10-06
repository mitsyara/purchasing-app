<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends \Spatie\Activitylog\Models\Activity
{
    protected $casts = [
        'properties' => 'json',
    ];

    // Helpers
    public function getLabel(): ?string
    {
        $title = \Illuminate\Support\Str::of($this->subject_type)->afterLast('App\Models\\')->headline()->toString();
        return $title . ' #' . $this->subject_id;
    }

}
