<?php

namespace App\Domains\Analytics\Models;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;

use Illuminate\Database\Eloquent\Model;

class CenterOccupancy extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'center_occupancies';
    public $timestamps = false;

    protected $fillable = [
        'evacuation_center_id',
        'current_occupancy',
        'last_updated',
    ];

    protected $casts = ['last_updated' => 'datetime'];

    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }
}