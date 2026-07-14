<?php

namespace App\Domains\EvacuationEvents\Models;

use App\Domains\CenterIssueReports\Models\CenterIssueReport;

use Illuminate\Database\Eloquent\Model;

class SeverityLevel extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'severity_levels';
    protected $primaryKey = 'severity_id';
    public $timestamps = false;

    protected $fillable = ['severity_key', 'severity_label'];

    public function disasterTypes()
    {
        return $this->hasMany(DisasterType::class, 'severity_level', 'severity_id');
    }

    public function centerIssueReports()
    {
        return $this->hasMany(CenterIssueReport::class, 'severity_id', 'severity_id');
    }
}