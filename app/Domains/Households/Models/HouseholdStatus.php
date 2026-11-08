<?php

namespace App\Domains\Households\Models;

use App\Domains\ReferenceData\Models\Address;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Evacuations\Models\EvacuatedMember;
use App\Domains\ReferenceData\Models\Gender;
use App\Domains\ReferenceData\Models\Relationship;
use App\Domains\ReferenceData\Models\CivilStatus;
use App\Domains\ReferenceData\Models\MemberVulnerableGroup;
use App\Domains\EvacuationEvents\Models\DisasterEvent;

use Illuminate\Database\Eloquent\Model;

class HouseholdStatus extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Status ID Constants
    |--------------------------------------------------------------------------
    | These must match the seeded values in the household_statuses table.
    */
    const NOT_VERIFIED = 1;
    const EVACUATED    = 2;
    const CHECKED_OUT  = 6;

    protected $connection = 'mysql_v2';
    protected $table = 'household_statuses';
    protected $primaryKey = 'status_id';
    public $timestamps = false;

    protected $fillable = ['status_id', 'status_key', 'status_label'];

    public static function ensureSeeded(): void
    {
        $required = [
            1 => ['status_id' => 1, 'status_key' => 'active',        'status_label' => 'Active'],
            2 => ['status_id' => 2, 'status_key' => 'evacuated',     'status_label' => 'Evacuated'],
            3 => ['status_id' => 3, 'status_key' => 'not_evacuated', 'status_label' => 'Not Evacuated'],
            4 => ['status_id' => 4, 'status_key' => 'relocated',     'status_label' => 'Relocated'],
            5 => ['status_id' => 5, 'status_key' => 'displaced',     'status_label' => 'Displaced'],
            6 => ['status_id' => 6, 'status_key' => 'returned',      'status_label' => 'Returned'],
        ];

        $existingIds = static::whereIn('status_id', array_keys($required))
            ->pluck('status_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (in_array(self::EVACUATED, $existingIds, true) && in_array(self::CHECKED_OUT, $existingIds, true)) {
            return;
        }

        $existingKeys = static::pluck('status_id', 'status_key')->toArray();

        foreach ($required as $id => $row) {
            if (in_array($id, $existingIds, true)) {
                continue;
            }

            $key = $row['status_key'];
            if (isset($existingKeys[$key]) && (int) $existingKeys[$key] !== $id) {
                $row['status_key'] = $key . '_' . $id;
            }

            static::insertOrIgnore([$row]);
        }
    }

    public function evacuationRecords()
    {
        return $this->hasMany(EvacuationRecord::class, 'status_id', 'status_id');
    }
}