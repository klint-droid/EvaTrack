<?php

namespace App\Domains\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsJobStatus extends Model
{
    protected $connection = 'mysql_v2';

    protected $table = 'analytics_job_status';

    protected $primaryKey = 'status_id';

    public $timestamps = false;

    protected $fillable = [

        'status_key',
        'status_label',
    ];


    public function jobLogs()
    {
        return $this->hasMany(
            AnalyticsJobLog::class,
            'status_id',
            'status_id'
        );
    }
}