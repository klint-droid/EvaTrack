<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UnitAllocation extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'unit_allocations';
    protected $primaryKey = 'allocation_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'allocation_id',
        'evacuation_id',
        'unit_id',
        'assigned_by',
        'selected_by_resident',
        'created_at',
    ];

    public function evacuation()
    {
        return $this->belongsTo(EvacuationRecord::class, 'evacuation_id', 'evacuation_id');
    }

    public function unit()
    {
        return $this->belongsTo(AccommodationUnit::class, 'unit_id', 'unit_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->allocation_id) {
                do {
                    $id = 'UA-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('allocation_id', $id)->exists());
                $model->allocation_id = $id;
            }
            $model->created_at = now();
        });
    }
}