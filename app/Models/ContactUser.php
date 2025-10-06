<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ContactUser extends Pivot
{
    use \App\Traits\HasLoggedActivity;
    public $incrementing = true;
}
