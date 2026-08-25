<?php

namespace App\Domains\Analytics\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Evacuations\Models\EvacuatedMember;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\EvacuationEvents\Models\DisasterEvent;
use App\Domains\Households\Models\HouseholdStatus;
use App\Domains\ResourceRequests\Models\ResourceRequest;
use App\Domains\ResourceRequests\Models\ResourceRequestStatus;
use App\Domains\Notifications\Models\UrgencyLevel;
use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use App\Domains\CenterIssueReports\Models\CenterIssueReportStatus;
use App\Domains\EvacuationEvents\Models\SeverityLevel;
use App\Domains\CenterIssueReports\Models\CenterIssueCategory;

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

    public function getDashboardAnalytics($eventId, $centerId = null, $startDate = null, $endDate = null)
    {
        return [
            'summary'             => $this->getSummaryKPIs($eventId, $centerId, $startDate, $endDate),
            'evacuation_trends'   => $this->getEvacuationTrends($eventId, $centerId, $startDate, $endDate),
            'status_distribution' => $this->getStatusDistribution($eventId, $centerId, $startDate, $endDate),
            'demographics'        => $this->getDemographics($eventId, $centerId, $startDate, $endDate),
            'center_performance'  => $this->getCenterPerformance($eventId, $centerId, $startDate, $endDate),
            'resource_requests'   => $this->getResourceRequestMetrics($eventId, $centerId, $startDate, $endDate),
            'center_issues'       => $this->getCenterIssueMetrics($eventId, $centerId, $startDate, $endDate),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | SUMMARY KPIs
    |--------------------------------------------------------------------------
    */

    protected function getSummaryKPIs($eventId, $centerId = null, $startDate = null, $endDate = null)
    {
        $query = EvacuationRecord::query();

        if ($eventId !== 'all') {
            $query->where('event_id', $eventId);
        }

        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
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

    protected function getEvacuationTrends($eventId, $centerId = null, $startDate = null, $endDate = null)
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

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
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

    protected function getStatusDistribution($eventId, $centerId = null, $startDate = null, $endDate = null)
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

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
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

    protected function getDemographics($eventId, $centerId = null, $startDate = null, $endDate = null)
    {
        $evacuationRecords = EvacuationRecord::query()
            ->when($eventId !== 'all', fn($q) => $q->where('event_id', $eventId))
            ->when($centerId, fn($q) => $q->where('center_id', $centerId))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->get();

        $evacuationIds = $evacuationRecords->pluck('evacuation_id');
        $householdIds = $evacuationRecords->pluck('household_id')->unique()->filter();

        $evacuatedMembers = EvacuatedMember::with([
            'member.gender',
            'member.vulnerableGroupDetails',
        ])
        ->whereIn('evacuation_id', $evacuationIds)
        ->get();

        $memberList = collect();
        foreach ($evacuatedMembers as $em) {
            if ($em->member) {
                $memberList->push($em->member);
            }
        }

        // Fallback: If no explicit evacuated_members records matched, load members of evacuated households
        if ($memberList->isEmpty() && $householdIds->isNotEmpty()) {
            $memberList = \App\Domains\Households\Models\HouseholdMember::with([
                'gender',
                'vulnerableGroupDetails',
            ])
            ->whereIn('household_id', $householdIds)
            ->get();
        }

        // Age groups
        $children = 0;
        $youth    = 0;
        $adults   = 0;
        $elderly  = 0;

        // Gender
        $male   = 0;
        $female = 0;

        // Vulnerable groups
        $vulnerableCounts = [];

        foreach ($memberList as $member) {
            if (!$member) continue;

            // Age
            if ($member->birth_date) {
                $age = Carbon::parse($member->birth_date)->age;
                if ($age <= 12) $children++;
                elseif ($age >= 13 && $age <= 17) $youth++;
                elseif ($age >= 18 && $age <= 59) $adults++;
                elseif ($age >= 60) $elderly++;
            }

            // Gender
            $genderModel = $member->getRelation('gender') ?? $member->gender;
            $genderKey = strtolower($genderModel?->gender_key ?? '');
            if ($genderKey === 'male') $male++;
            elseif ($genderKey === 'female') $female++;

            // Vulnerable groups
            $vgroups = $member->getRelation('vulnerableGroupDetails') ?? $member->vulnerableGroupDetails;
            if ($vgroups) {
                foreach ($vgroups as $vg) {
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
                ['group' => 'Youth (13-17)',   'count' => $youth],
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

    protected function getCenterPerformance($eventId, $centerId = null, $startDate = null, $endDate = null)
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

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
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

            'total_households' =>

                $members
                    ->pluck(
                        'evacuationRecord.household_id'
                    )
                    ->unique()
                    ->count(),

            'male_count' =>

                $members->filter(function ($m) {

                    $member = $m->member;
                    if(!$member) return false;
                    $genderModel = $member->getRelation('gender');
                    return $genderModel?->gender_key === 'male';

                })->count(),

            'female_count' =>

                $members->filter(function ($m) {

                    $member = $m->member;
                    if(!$member) return false;
                    $genderModel = $member->getRelation('gender');
                    return $genderModel?->gender_key === 'female';

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

    protected function getScopedQuery($modelClass, $eventId, $centerId = null, $startDate = null, $endDate = null)
    {
        $query = $modelClass::query();

        if ($centerId) {
            $query->where('evacuation_center_id', $centerId);
        } elseif ($eventId !== 'all') {
            $centerIds = EvacuationRecord::where('event_id', $eventId)->pluck('center_id')->unique()->filter();
            $query->whereIn('evacuation_center_id', $centerIds);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    protected function getResourceRequestMetrics($eventId, $centerId = null, $startDate = null, $endDate = null)
    {
        $query = $this->getScopedQuery(ResourceRequest::class, $eventId, $centerId, $startDate, $endDate);

        // Status counts
        $statusCounts = $query->clone()
            ->select('status_id', DB::raw('COUNT(*) as count'))
            ->groupBy('status_id')
            ->get();

        $statuses = ResourceRequestStatus::all()->keyBy('status_id');
        $statusData = $statusCounts->map(function ($row) use ($statuses) {
            $s = $statuses->get($row->status_id);
            return [
                'status_key'   => $s?->status_key ?? 'unknown',
                'status_label' => $s?->status_label ?? 'Unknown',
                'count'        => (int) $row->count,
            ];
        })->values();

        // Urgency counts
        $urgencyCounts = $query->clone()
            ->select('urgency_id', DB::raw('COUNT(*) as count'))
            ->groupBy('urgency_id')
            ->get();

        $urgencies = UrgencyLevel::all()->keyBy('urgency_id');
        $urgencyData = $urgencyCounts->map(function ($row) use ($urgencies) {
            $u = $urgencies->get($row->urgency_id);
            return [
                'urgency_key'   => $u?->urgency_key ?? 'unknown',
                'urgency_label' => $u?->urgency_label ?? 'Unknown',
                'count'        => (int) $row->count,
            ];
        })->values();

        // Top requested types
        $typeCounts = $query->clone()
            ->select('resource_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('resource_type')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'type'           => $row->resource_type,
                    'count'          => (int) $row->count,
                    'total_quantity' => (int) $row->total_qty,
                ];
            });

        return [
            'status_distribution'  => $statusData,
            'urgency_distribution' => $urgencyData,
            'top_types'            => $typeCounts,
        ];
    }

    protected function getCenterIssueMetrics($eventId, $centerId = null, $startDate = null, $endDate = null)
    {
        $query = $this->getScopedQuery(CenterIssueReport::class, $eventId, $centerId, $startDate, $endDate);

        // Status counts
        $statusCounts = $query->clone()
            ->select('status_id', DB::raw('COUNT(*) as count'))
            ->groupBy('status_id')
            ->get();

        $statuses = CenterIssueReportStatus::all()->keyBy('status_id');
        $statusData = $statusCounts->map(function ($row) use ($statuses) {
            $s = $statuses->get($row->status_id);
            return [
                'status_key'   => $s?->status_key ?? 'unknown',
                'status_label' => $s?->status_label ?? 'Unknown',
                'count'        => (int) $row->count,
            ];
        })->values();

        // Severity counts
        $severityCounts = $query->clone()
            ->select('severity_id', DB::raw('COUNT(*) as count'))
            ->groupBy('severity_id')
            ->get();

        $severities = SeverityLevel::all()->keyBy('severity_id');
        $severityData = $severityCounts->map(function ($row) use ($severities) {
            $s = $severities->get($row->severity_id);
            return [
                'severity_key'   => $s?->severity_key ?? 'unknown',
                'severity_label' => $s?->severity_label ?? 'Unknown',
                'count'        => (int) $row->count,
            ];
        })->values();

        // Category counts
        $categoryCounts = $query->clone()
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->get();

        $categories = CenterIssueCategory::all()->keyBy('category_id');
        $categoryData = $categoryCounts->map(function ($row) use ($categories) {
            $c = $categories->get($row->category_id);
            return [
                'category_key'   => $c?->category_key ?? 'unknown',
                'category_label' => $c?->category_label ?? 'Unknown',
                'count'        => (int) $row->count,
            ];
        })->values();

        return [
            'status_distribution'   => $statusData,
            'severity_distribution' => $severityData,
            'category_distribution' => $categoryData,
        ];
    }
}