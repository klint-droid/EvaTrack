<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterIssueReportStatus extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'center_issue_report_statuses';
    protected $primaryKey = 'status_id';
    public $timestamps = false;

    protected $fillable = ['status_key', 'status_label'];

    public function reports()
    {
        return $this->hasMany(CenterIssueReport::class, 'status_id', 'status_id');
    }
}