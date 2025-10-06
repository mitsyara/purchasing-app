<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyUser extends Pivot
{
    use \App\Traits\HasLoggedActivity;
    public $incrementing = true;
}
