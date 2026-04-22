<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvacuatedMember extends Model
{
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'evacuated_member_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evacuatedMember) {

            $prefix = 'EM';
            $year = date('Y');

            do {
                $random = strtoupper(Str::random(6));
                $id = "{$prefix}-{$year}-{$random}";
            } while (self::where('evacuated_member_id', $id)->exists());

            $evacuatedMember->evacuated_member_id = $id;
        });
    }

    protected $fillable = [
        'evacuation_id',
        'member_id',
        'verified_at'
    ];

    public function evacuation()
    {
        return $this->belongsTo(EvacuationRecord::class, 'evacuation_id');
    }

    public function member()
    {
        return $this->belongsTo(HouseholdMember::class, 'member_id');
    }
}