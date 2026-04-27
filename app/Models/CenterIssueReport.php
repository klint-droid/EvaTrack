<?php

namespace App\Models;

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
        'report_id',
        'evacuation_center_id',
        'reported_by',
        'handled_by',
        'category',
        'title',
        'description',
        'severity',
        'status',
    ];

    protected static function boot(){
        parent::boot();

        static::creating(function ($model){
            if(!$model->report_id){
                do {
                    $id = 'CIR-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('report_id', $id)->exists());

                $model->report_id = $id;
            }
        });
    }

    public function center(){
        return $this->belongsTo(
            EvacuationCenter::class,
            'evacuation_center_id',
            'evacuation_center_id'
        );
    }

    public function reportedBy(){
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    public function handledBy(){
        return $this->belongsTo(User::class, 'handled_by', 'user_id');
    }
}
