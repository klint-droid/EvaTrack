<?php

namespace App\Domains\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class RecurrenceType extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'recurrence_types';
    protected $primaryKey = 'type_id';
    public $timestamps = false;

    protected $fillable = ['type_key', 'type_label'];

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recurrence_type_id', 'type_id');
    }
}