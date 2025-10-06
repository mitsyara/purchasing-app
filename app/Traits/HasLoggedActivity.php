<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait HasLoggedActivity
{
    use LogsActivity;

    // Customize the log options for models
    protected static function booted(): void
    {
        static::addLogChange(new \App\Pipes\SpatieRemoveKeyFromLogChangesPipe);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
