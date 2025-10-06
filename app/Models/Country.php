<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use \App\Traits\HasLoggedActivity;
    protected $fillable = [
        'alpha2',
        'alpha3',
        'country_name',
        'phone_code',
        'curr_code',
        'curr_name',
        'is_fav',
        'notes',
    ];
}
