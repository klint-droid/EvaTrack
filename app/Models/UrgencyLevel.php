<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UrgencyLevel extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'urgency_levels';
    protected $primaryKey = 'urgency_id';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'urgency_id',
        'urgency_key',
        'urgency_label',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->urgency_id) {
                do {
                    $id = 'URG-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('urgency_id', $id)->exists());

                $model->urgency_id = $id;
            }
        });
    }

    public function resourceRequests()
    {
        return $this->hasMany(ResourceRequest::class, 'urgency_id', 'urgency_id');
    }
}