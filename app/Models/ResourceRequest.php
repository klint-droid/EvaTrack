<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ResourceRequest extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'resource_requests';
    protected $primaryKey = 'request_id';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'request_id',
        'evacuation_center_id',
        'requested_by',
        'handled_by',
        'request_type',
        'resource_type',
        'quantity',
        'description',
        'urgency_id',
        'status',
        'target_agency',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->request_id) {
                do {
                    $id = 'RR-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('request_id', $id)->exists());

                $model->request_id = $id;
            }
        });
    }

    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by', 'user_id');
    }

    public function urgency()
    {
        return $this->belongsTo(UrgencyLevel::class, 'urgency_id', 'urgency_id');
    }
}