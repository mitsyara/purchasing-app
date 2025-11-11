<?php

namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    use \App\Traits\HasLoggedActivity;
}
