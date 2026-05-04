<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Household extends Model
{
    use SoftDeletes;
    protected $connection = 'mysql_v2';
    protected $primaryKey = 'household_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->household_id) {
                do {
                    $id = 'NHH-' . date('Y') . '-' . strtoupper(Str::random(6));
                } while (self::where('household_id', $id)->exists());

                $model->household_id = $id;
            }
        });
    }

    protected $fillable = [
        'household_name',
        'address_id',
        'contact_number',
        'emergency_contact',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    public function user()
    {
        return $this->hasOne(User::class, 'household_id', 'household_id');
    }
    public function members()
    {
        return $this->hasMany(HouseholdMember::class, 'household_id');
    }

    public function evacuations()
    {
        return $this->hasMany(EvacuationRecord::class, 'household_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id', 'address_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class, 'household_id', 'household_id');
    }

    public function notificationRecepients()
    {
        return $this->hasMany(NotificationRecipient::class, 'household_id', 'household_id');
    }

    public function currentEvacuation(){
        return $this->hasOne(EvacuationRecord::class, 'household_id')
            ->where('household_status_id', 2)
            ->latest();
    }

    public function currentAllocation(){
        return $this->hasOneThrough(
            UnitAllocation::class,
            EvacuationRecord::class,
            'household_id',
            'evacuation_id',
            'household_id',
            'evacuation_id'
        );
    }
}