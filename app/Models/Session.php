<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    protected $table = 'sessions';
    public $incrementing = false;
    protected $keyType = 'string';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Casting attribute
    protected function lastActivityAt(): Attribute
    {
        return Attribute::get(fn() => $this->last_activity
            ? \Carbon\Carbon::createFromTimestamp($this->last_activity, 'Asia/Ho_Chi_Minh')
            : null);
    }
}
