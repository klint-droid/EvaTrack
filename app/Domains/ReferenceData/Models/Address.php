<?php

namespace App\Domains\ReferenceData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;
    protected $connection = 'mysql_v2';
    protected $table = 'addresses';
    protected $primaryKey = 'address_id';
    public $timestamps = true;

    protected $fillable = [
        'street_address', 
        'barangay_id', 
        'sitio_id', 
        'purok_id', 
        'zipcode_id'
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    // Relationships
    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function sitio()
    {
        return $this->belongsTo(Sitio::class, 'sitio_id', 'sitio_id');
    }

    public function purok()
    {
        return $this->belongsTo(Purok::class, 'purok_id', 'purok_id');
    }

    public function zipcode()
    {
        return $this->belongsTo(ZipCode::class, 'zipcode_id', 'zipcode_id');
    }

    // Accessor to get full address
    public function getFullAddressAttribute()
    {
        $parts = [];
        
        if ($this->street_address) {
            $parts[] = $this->street_address;
        }
        
        if ($this->purok) {
            $parts[] = "Purok {$this->purok->purok_name}";
        } elseif ($this->sitio) {
            $parts[] = "Sitio {$this->sitio->sitio_name}";
        }
        
        if ($this->barangay) {
            $parts[] = $this->barangay->barangay_name;
        }
        
        if ($this->barangay && $this->barangay->city) {
            $parts[] = $this->barangay->city->city_name;
        }
        
        if ($this->barangay && $this->barangay->city && $this->barangay->city->province) {
            $parts[] = $this->barangay->city->province->province_name;
        }
        
        if ($this->zipcode) {
            $parts[] = $this->zipcode->zipcode;
        }
        
        return implode(', ', $parts);
    }
}