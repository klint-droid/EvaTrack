<?php

namespace App\Domains\Authentication\Models;

use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\EvacuationEvents\Models\DisasterEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CenterPersonnelAssignment extends Model
{
    use HasFactory;

    protected $connection = 'mysql_v2';
    protected $table = 'center_personnel_assignments';
    protected $primaryKey = 'assignment_id';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'evacuation_center_id',
        'assigned_by',
        'event_id',
        'role_at_center',
        'status',
        'assigned_at',
        'relieved_at',
        'remarks',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'relieved_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Scope query to only include active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereNull('relieved_at');
    }

    /**
     * The assigned personnel.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * The evacuation center the personnel is assigned to.
     */
    public function center()
    {
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id', 'evacuation_center_id');
    }

    /**
     * Alias for center relationship for semantic clarity.
     */
    public function evacuationCenter()
    {
        return $this->center();
    }

    /**
     * The admin user who made this assignment.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }

    /**
     * The disaster event active during this assignment (if applicable).
     */
    public function event()
    {
        return $this->belongsTo(DisasterEvent::class, 'event_id', 'event_id');
    }
}
