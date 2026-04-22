<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
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
        'user_id',
        'name',
        'password',
        'role_id',
        'contact_number',
        'assigned_center_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
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
}