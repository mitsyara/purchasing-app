<?php

namespace App\Models;

use App\Traits\HasLoggedActivity;

class Role extends \Spatie\Permission\Models\Role
{
    use HasLoggedActivity;
}
