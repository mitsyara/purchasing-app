<?php

namespace App\Pipes;

use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;

class SpatieRemoveKeyFromLogChangesPipe implements LoggablePipe
{
    public function __construct() {}

    public function handle(EventLogBag $event, \Closure $next): EventLogBag
    {
        $changes = $event->changes;

        // Lặp qua attributes và old
        foreach (['attributes', 'old'] as $type) {
            if (isset($changes[$type]) && is_array($changes[$type])) {
                foreach ($changes[$type] as $key => $value) {
                    $isEmpty = is_null($value)
                        || (is_string($value) && trim($value) === '')
                        || (is_array($value) && empty($value));
                    // Nếu là old mà attributes có key tương ứng thì giữ old
                    if ($isEmpty && !($type === 'old' && array_key_exists($key, $changes['attributes'] ?? []))) {
                        \Illuminate\Support\Arr::forget($changes, ["{$type}.{$key}"]);
                    }
                }
            }
        }

        $event->changes = $changes;

        return $next($event);
    }
}
