<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceRequestStatus extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'resource_request_status';
    protected $primaryKey = 'status_id';
    public $timestamps = false;

    protected $fillable = ['status_key', 'status_label'];

    public function resourceRequests()
    {
        return $this->hasMany(ResourceRequest::class, 'status_id', 'status_id');
    }
}