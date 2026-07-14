<?php

namespace App\Domains\CenterIssueReports\Models;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Authentication\Models\User;
use App\Domains\EvacuationEvents\Models\SeverityLevel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CenterIssueReport extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'center_issue_reports';
    protected $primaryKey = 'report_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'evacuation_center_id',
        'reported_by',
        'handled_by',
        'category_id',
        'title',
        'description',
        'severity_id',
        'status_id',
        'attachment_path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->report_id) {
                do {
                    $id = 'CIR-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('report_id', $id)->exists());

                $model->report_id = $id;
            }
        });
    }
    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by', 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(CenterIssueCategory::class, 'category_id', 'category_id');
    }

    public function severityLevel()
    {
        return $this->belongsTo(SeverityLevel::class, 'severity_id', 'severity_id');
    }

    public function status()
    {
        return $this->belongsTo(CenterIssueReportStatus::class, 'status_id', 'status_id');
    }
}