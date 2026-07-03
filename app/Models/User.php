<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use SoftDeletes;
    use HasFactory, Notifiable, HasApiTokens;

    const ROLE_SUPER_ADMIN_ID = 1;
    const ROLE_EVAC_ADMIN_ID = 2;
    const ROLE_EVAC_PERSONNEL_ID = 3;

    protected $connection = 'mysql_v2';
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'first_name',
        'last_name',
        'password',
        'role_id',
        'contact_number',
        'assigned_center_id',
        'is_active',
        'household_id',
        'profile_photo',
    ];

    protected $appends = ['name', 'profile_photo_url'];

    public function getNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }
        return null;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {

            $prefix = match ($user->role_id) {
                self::ROLE_SUPER_ADMIN_ID => 'SUP',
                self::ROLE_EVAC_ADMIN_ID => 'EAD',
                self::ROLE_EVAC_PERSONNEL_ID => 'EPE',
                default => 'UNK',
            };

            $year = date('Y');

            do {
                $random = strtoupper(Str::random(6));
                $userId = "{$prefix}-{$year}-{$random}";
            } while (self::where('user_id', $userId)->exists());

            $user->user_id = $userId;
        });
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function household()
    {
        return $this->belongsTo(Household::class, 'household_id', 'household_id');
    }
    public function assignedCenter()
    {
        return $this->belongsTo(EvacuationCenter::class, 'assigned_center_id');
    }

    public function isSuperAdmin()
    {
        return $this->role_id === self::ROLE_SUPER_ADMIN_ID;
    }

    public function isEvacAdmin()
    {
        return $this->role_id === self::ROLE_EVAC_ADMIN_ID;
    }

    public function isEvacPersonnel()
    {
        return $this->role_id === self::ROLE_EVAC_PERSONNEL_ID;
    }

    public function canManageUsers()
    {
        return $this->isSuperAdmin() || $this->isEvacAdmin();
    }

    public function getAuthIdentifierName()
    {
        return 'user_id';
    }

    public function hasCenterAccess($centerId)
    {
        if ($this->canManageUsers()) {
            return true;
        }

        return $this->assigned_center_id === $centerId;
    }

    public function isAssigned()
    {
        return !is_null($this->assigned_center_id);
    }

    public function verifiedEvacuationRecords(){
        return $this->hasMany(EvacuationRecord::class, 'user_id', 'user_id')->whereNotNull('verified_at');
    }

    public function assignedUnitAllocations()
    {
        return $this->hasMany(UnitAllocation::class, 'user_id', 'user_id');
    }

    public function reportedIssues()
    {
        return $this->hasMany(CenterIssueReport::class, 'reported_by', 'user_id');
    }

        public function resourceRequests()
    {
        return $this->hasMany(ResourceRequest::class, 'requested_by', 'user_id');
    }

    public function handledResourceRequests()
    {
        return $this->hasMany(ResourceRequest::class, 'handled_by', 'user_id');
    }

    public function notificationsSent()
    {
        return $this->hasMany(Notification::class, 'sent_by', 'user_id');
    }
}