<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisasterType extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_v2';

    protected $table = 'disaster_types';
    protected $primaryKey = 'type_id';

    protected $fillable = [
        'type_code',
        'type_name',
        'severity_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function severityLevel()
    {
        return $this->belongsTo(SeverityLevel::class, 'severity_level', 'severity_id');
    }

    public function events()
    {
        return $this->hasMany(DisasterEvent::class, 'type_id', 'type_id');
    }

    public function disasterEvents()
    {
        return $this->belongsToMany(
            DisasterEvent::class,
            'disaster_event_types',
            'type_id',
            'event_id'
        );
    }
}