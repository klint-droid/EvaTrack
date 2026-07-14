<?php

namespace App\Domains\CenterIssueReports\Models;

use Illuminate\Database\Eloquent\Model;

class CenterIssueCategory extends Model
{
    protected $connection = 'mysql_v2';
    protected $table = 'center_issue_categories';
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    protected $fillable = ['category_key', 'category_label'];

    public function reports()
    {
        return $this->hasMany(CenterIssueReport::class, 'category_id', 'category_id');
    }
}