<?php

namespace App\Domains\ReferenceData\Models;

use App\Domains\Households\Models\HouseholdMember;

use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'relationships';
    protected $primaryKey = 'relationship_id';
    public $timestamps = false;

    protected $fillable = ['relationship_key', 'relationship_label'];

    protected $appends = ['label'];

    public function getLabelAttribute()
    {
        return $this->relationship_label ?? ($this->attributes['label'] ?? null);
    }

    public function members()
    {
        return $this->hasMany(HouseholdMember::class, 'relationship', 'relationship_id');
    }
}

