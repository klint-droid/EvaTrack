<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'roles';
    protected $primaryKey = 'role_id';

    public $timestamps = false;
}