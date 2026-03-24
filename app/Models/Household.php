<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    //
    protected $primaryKey = 'household_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'household_id',
        'household_name',
        'phone_number',
        'qr_code'
    ];
}
