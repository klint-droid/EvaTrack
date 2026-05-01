<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'relationships';
    protected $primaryKey = 'relationship_id';
    public $timestamps = false;

    protected $fillable = ['relationship_key', 'relationship_label'];

    public function members()
    {
        return $this->hasMany(HouseholdMember::class, 'relationship', 'relationship_id');
    }
}

