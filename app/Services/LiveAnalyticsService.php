<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\EvacuationRecord;
use App\Models\EvacuatedMember;
use App\Models\EvacuationCenter;
use App\Models\DisasterEvent;
use App\Models\HouseholdStatus;

class LiveAnalyticsService
{
    /*
    |--------------------------------------------------------------------------
    | EVENTS LIST (for dropdown)
    |--------------------------------------------------------------------------
    */

    public function getEventsList()
    {
        return DisasterEvent::withCount('evacuationRecords')
            ->with('primaryType:type_id,type_name')
            ->orderByDesc('started_at')
            ->get()
            ->map(function ($event) {
                return [
                    'event_id'      => $event->event_id,
                    'name'          => $event->name,
                    'type'          => $event->primaryType?->type_name ?? 'Unknown',
                    'started_at'    => $event->started_at?->toDateTimeString(),
                    'ended_at'      => $event->ended_at?->toDateTimeString(),
                    'is_active'     => is_null($event->ended_at),
                    'record_count'  => $event->evacuation_records_count,
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function getDashboardAnalytics($eventId, $centerId = null)
    {
        return [
            'summary'             => $this->getSummaryKPIs($eventId, $centerId),
            'evacuation_trends'   => $this->getEvacuationTrends($eventId, $centerId),
            'status_distribution' => $this->getStatusDistribution($eventId, $centerId),
            'demographics'        => $this->getDemographics($eventId, $centerId),
            'center_performance'  => $this->getCenterPerformance($eventId, $centerId),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | SUMMARY KPIs
    |--------------------------------------------------------------------------
    */

    protected function getSummaryKPIs($eventId, $centerId = null)
    {
        $query = EvacuationRecord::query();

        if ($eventId !== 'all') {
            $query->where('event_id', $eventId);
        }

        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        $records = $query->get();

        $totalHouseholds  = $records->pluck('household_id')->unique()->count();
        $totalIndividuals = $records->sum('evacuated_count');

        $centerIds = $records->pluck('center_id')->unique()->filter();

        $centers = EvacuationCenter::whereIn('evacuation_center_id', $centerIds)->get();
        $totalCenters = $centers->count();

        $totalCapacity   = $centers->sum('capacity');
        $totalOccupancy  = $totalIndividuals;
        $avgOccupancyPct = $totalCapacity > 0
            ? round(($totalOccupancy / $totalCapacity) * 100, 1)
            : 0;

        return [
            'total_households'    => $totalHouseholds,
            'total_individuals'   => (int) $totalIndividuals,
            'active_centers'      => $totalCenters,
            'avg_occupancy_pct'   => $avgOccupancyPct,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | EVACUATION TRENDS (daily intake)
    |--------------------------------------------------------------------------
    */

    protected function getEvacuationTrends($eventId, $centerId = null)
    {
        $query = EvacuationRecord::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as households'),
            DB::raw('SUM(evacuated_count) as individuals')
        )->whereNotNull('created_at');

        if ($eventId !== 'all') {
            $query->where('event_id', $eventId);
        }

        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        return $query
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date'        => $row->date,
                    'households'  => (int) $row->households,
                    'individuals' => (int) $row->individuals,
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS DISTRIBUTION
    |--------------------------------------------------------------------------
    */

    protected function getStatusDistribution($eventId, $centerId = null)
    {
        $query = EvacuationRecord::select(
            'household_status_id',
            DB::raw('COUNT(*) as count')
        );

        if ($eventId !== 'all') {
            $query->where('event_id', $eventId);
        }

        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        $distribution = $query
            ->groupBy('household_status_id')
            ->get();

        $statuses = HouseholdStatus::all()->keyBy('status_id');

        return $distribution->map(function ($row) use ($statuses) {
            $status = $statuses->get($row->household_status_id);
            return [
                'status_id'    => $row->household_status_id,
                'status_key'   => $status?->status_key ?? 'unknown',
                'status_label' => $status?->status_label ?? 'Unknown',
                'count'        => (int) $row->count,
            ];
        })->values();
    }

    /*
    |--------------------------------------------------------------------------
    | DEMOGRAPHICS
    |--------------------------------------------------------------------------
    */

    protected function getDemographics($eventId, $centerId = null)
    {
        $evacuationIds = EvacuationRecord::query()
            ->when($eventId !== 'all', fn($q) => $q->where('event_id', $eventId))
            ->when($centerId, fn($q) => $q->where('center_id', $centerId))
            ->pluck('evacuation_id');

        $members = EvacuatedMember::with([
            'member.gender',
            'member.vulnerableGroupDetails',
        ])
        ->whereIn('evacuation_id', $evacuationIds)
        ->get();

        // Age groups
        $children = 0;
        $adults   = 0;
        $elderly  = 0;

        // Gender
        $male   = 0;
        $female = 0;

        // Vulnerable groups
        $vulnerableCounts = [];

        foreach ($members as $em) {
            $member = $em->member;
            if (!$member) continue;

            // Age
            if ($member->birth_date) {
                $age = Carbon::parse($member->birth_date)->age;
                if ($age <= 12) $children++;
                elseif ($age >= 18 && $age <= 59) $adults++;
                elseif ($age >= 60) $elderly++;
            }

            // Gender
            $genderKey = $member->gender?->gender_key;
            if ($genderKey === 'male') $male++;
            elseif ($genderKey === 'female') $female++;

            // Vulnerable groups
            if ($member->vulnerableGroupDetails) {
                foreach ($member->vulnerableGroupDetails as $vg) {
                    $key = $vg->vulnerable_group_key;
                    $label = $vg->vulnerable_group_label;
                    if (!isset($vulnerableCounts[$key])) {
                        $vulnerableCounts[$key] = ['key' => $key, 'label' => $label, 'count' => 0];
                    }
                    $vulnerableCounts[$key]['count']++;
                }
            }
        }

        return [
            'age_groups' => [
                ['group' => 'Children (0-12)', 'count' => $children],
                ['group' => 'Adults (18-59)',  'count' => $adults],
                ['group' => 'Elderly (60+)',   'count' => $elderly],
            ],
            'gender' => [
                ['gender' => 'Male',   'count' => $male],
                ['gender' => 'Female', 'count' => $female],
            ],
            'vulnerable_groups' => array_values($vulnerableCounts),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CENTER PERFORMANCE
    |--------------------------------------------------------------------------
    */

    protected function getCenterPerformance($eventId, $centerId = null)
    {
        $query = EvacuationRecord::select(
            'center_id',
            DB::raw('COUNT(DISTINCT household_id) as household_count'),
            DB::raw('SUM(evacuated_count) as individual_count')
        );

        if ($eventId !== 'all') {
            $query->where('event_id', $eventId);
        }

        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        $recordsByCenter = $query
            ->groupBy('center_id')
            ->get()
            ->keyBy('center_id');

        $centerIds = $recordsByCenter->keys();

        $centers = EvacuationCenter::whereIn('evacuation_center_id', $centerIds)->get();

        return $centers->map(function ($center) use ($recordsByCenter) {
            $stats = $recordsByCenter->get($center->evacuation_center_id);
            $capacity       = (int) $center->capacity;
            $occupancy      = (int) ($stats?->individual_count ?? 0);
            $utilizationPct = $capacity > 0 ? round(($occupancy / $capacity) * 100, 1) : 0;

            return [
                'center_id'       => $center->evacuation_center_id,
                'name'            => $center->name,
                'capacity'        => $capacity,
                'occupancy'       => $occupancy,
                'households'      => (int) ($stats?->household_count ?? 0),
                'utilization_pct' => $utilizationPct,
                'status'          => $utilizationPct >= 90 ? 'critical'
                                   : ($utilizationPct >= 70 ? 'warning' : 'normal'),
            ];
        })
        ->sortByDesc('utilization_pct')
        ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY METHODS (preserved for backward compatibility)
    |--------------------------------------------------------------------------
    */

    public function getEventAnalytics($eventId)
    {
        $evacuationIds = EvacuationRecord::where(
            'event_id',
            $eventId
        )->pluck('evacuation_id');

        $members = EvacuatedMember::with([

            'member.gender',
            'member.vulnerableGroupDetails',
            'evacuationRecord',

        ])
        ->whereIn(
            'evacuation_id',
            $evacuationIds
        )
        ->get();

        return $this->buildLegacyAnalytics($members);
    }

    public function getCenterAnalytics(
        $eventId,
        $centerId
    ) {

        $evacuationIds = EvacuationRecord::where(
            'event_id',
            $eventId
        )
        ->where(
            'center_id',
            $centerId
        )
        ->pluck('evacuation_id');

        $members = EvacuatedMember::with([

            'member.gender',
            'member.vulnerableGroupDetails',
            'evacuationRecord',

        ])
        ->whereIn(
            'evacuation_id',
            $evacuationIds
        )
        ->get();

        return $this->buildLegacyAnalytics($members);
    }

    protected function buildLegacyAnalytics($members)
    {
        return [

            'total_population' =>

                $members->count(),

            'total_household' =>

                $members
                    ->pluck(
                        'evacuationRecord.household_id'
                    )
                    ->unique()
                    ->count(),

            'male_count' =>

                $members->filter(function ($m) {

                    return optional(
                        optional($m->member)->gender
                    )->gender_key === 'male';

                })->count(),

            'female_count' =>

                $members->filter(function ($m) {

                    return optional(
                        optional($m->member)->gender
                    )->gender_key === 'female';

                })->count(),

            'children_count' =>

                $members->filter(function ($m) {

                    $birthDate =
                        optional($m->member)->birth_date;

                    if (!$birthDate) {
                        return false;
                    }

                    $age = Carbon::parse(
                        $birthDate
                    )->age;

                    return $age <= 12;

                })->count(),

            'adult_count' =>

                $members->filter(function ($m) {

                    $birthDate =
                        optional($m->member)->birth_date;

                    if (!$birthDate) {
                        return false;
                    }

                    $age = Carbon::parse(
                        $birthDate
                    )->age;

                    return $age >= 18
                        && $age <= 59;

                })->count(),

            'elderly_count' =>

                $members->filter(function ($m) {

                    $birthDate =
                        optional($m->member)->birth_date;

                    if (!$birthDate) {
                        return false;
                    }

                    $age = Carbon::parse(
                        $birthDate
                    )->age;

                    return $age >= 60;

                })->count(),

            'pwd_count' =>

                $members->filter(function ($m) {

                    return optional($m->member)
                        ->vulnerableGroupDetails
                        ->contains(
                            'vulnerable_group_key',
                            'pwd'
                        );

                })->count(),

            'pregnant_count' =>

                $members->filter(function ($m) {

                    return optional($m->member)
                        ->vulnerableGroupDetails
                        ->contains(
                            'vulnerable_group_key',
                            'pregnant'
                        );

                })->count(),
        ];
    }
}