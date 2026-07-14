<?php

namespace App\Domains\EvacuationEvents\Models;

use Illuminate\Database\Eloquent\Model;

class DisasterEventType extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'disaster_event_types';
    protected $primaryKey = 'event_type_id';
    public $timestamps = false;

    protected $fillable = ['event_id', 'type_id'];

    public function event()
    {
        return $this->belongsTo(DisasterEvent::class, 'event_id', 'event_id');
    }

    public function disasterType()
    {
        return $this->belongsTo(DisasterType::class, 'type_id', 'type_id');
    }
}