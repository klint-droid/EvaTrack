<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPER_ADMIN = 'super_admin';

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(){
        parent::boot();

        static::creating(function ($user) {
            $prefix = match ($user->role){
                self::ROLE_ADMIN => 'ADM',
                self::ROLE_SUPER_ADMIN => 'SUP',
                default => 'PER',
            };

            $year = date('Y');

            do{
                $random = strtoupper(Str::random(6));
                $userId = "{$prefix}-{$year}-{$random}";
            } while(self::where('user_id', $userId)->exists());

            $user->user_id = $userId;
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'assigned_evacuation_center_id',
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isUser()
    {
        return $this->role === self::ROLE_USER;
    }

    public function canManageUsers(){
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    public function getAuthIdentifierName()
    {
        return 'user_id';
    }
    
    public function evacuationCenter(){
        return $this->belongsTo(
            EvacuationCenter::class,
            'assigned_evacuation_center_id',
            'evacuation_center_id'
        );
    }

    public function hasCenterAccess($centerId){
        if(this->isAdmin() || $this->isSuperAdmin()){
            return true;
        }

        return $this->assigned_evacuation_center_id === $centerId;
    }

    public function isAssigned(){
        return !is_null($this->assigned_evacuation_center_id);
    }
}
