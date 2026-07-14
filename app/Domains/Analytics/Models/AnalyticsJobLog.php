<?php

namespace App\Domains\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsJobLog extends Model
{
    protected $connection = 'mysql_v2';

    protected $table = 'analytics_job_logs';

    protected $primaryKey = 'job_id';

    public $timestamps = false;

    protected $fillable = [

        'status_id',

        'started_at',
        'finished_at',

        'message',
    ];

    protected $casts = [

        'started_at' => 'datetime',

        'finished_at' => 'datetime',
    ];

    public function status()
    {
        return $this->belongsTo(
            AnalyticsJobStatus::class,
            'status_id',
            'status_id'
        );
    }


    public function isRunning()
    {
        return $this->status?->status_key === 'processing';
    }

    public function isSuccess()
    {
        return $this->status?->status_key === 'completed';
    }

    public function isFailed()
    {
        return $this->status?->status_key === 'failed';
    }
}