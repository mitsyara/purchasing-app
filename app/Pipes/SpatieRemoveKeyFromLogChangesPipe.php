<?php

namespace App\Pipes;

use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;
use Illuminate\Support\Arr;

class SpatieRemoveKeyFromLogChangesPipe implements LoggablePipe
{
    public function __construct() {}

    public function handle(EventLogBag $event, \Closure $next): EventLogBag
    {
        $changes = $event->changes;

        foreach (['attributes', 'old'] as $type) {
            if (isset($changes[$type]) && is_array($changes[$type])) {
                foreach ($changes[$type] as $key => $value) {
                    $isEmpty = is_null($value)
                        || (is_string($value) && trim($value) === '')
                        || (is_array($value) && empty($value));

                    // Giữ lại old nếu có attributes tương ứng
                    if ($isEmpty && !($type === 'old' && array_key_exists($key, $changes['attributes'] ?? []))) {
                        Arr::forget($changes, ["{$type}.{$key}"]);
                    }
                }
            }
        }

        // attributes' và 'old' đều rỗng → clear changes
        if (
            empty($changes['attributes'] ?? []) &&
            empty($changes['old'] ?? [])
        ) {
            // Trả rỗng => bỏ qua log
            $event->changes = [];
            // Dừng pipeline
            return $event;
        }

        $event->changes = $changes;
        return $next($event);
    }
}
