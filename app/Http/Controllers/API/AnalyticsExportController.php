<?php

namespace App\Http\Controllers\API;

use App\Models\EvacuationRecord;
use App\Models\EvacuationCenter;
use App\Models\EvacuatedMember;
use App\Models\DisasterEvent;
use App\Models\ResourceRequest;
use App\Models\CenterIssueReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class AnalyticsExportController extends BaseApiController
{
    // ─── SHARED FILTER HELPERS ───



    private function applyFilters(Request $request, $query, $centerField = 'center_id', $dateField = 'created_at')
    {
        $user = Auth::user();

        if ($request->filled('event_id') && $request->event_id !== 'all') {
            $query->where('event_id', $request->event_id);
        }

        if ($user->isEvacPersonnel() && $user->assigned_center_id) {
            $query->where($centerField, $user->assigned_center_id);
        } elseif ($request->filled('center_id') && $request->center_id !== 'all') {
            $query->where($centerField, $request->center_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate($dateField, '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate($dateField, '<=', $request->end_date);
        }

        return $query;
    }

    private function getFilterMeta(Request $request): array
    {
        $eventName = 'All Events';
        if ($request->filled('event_id') && $request->event_id !== 'all') {
            $event = DisasterEvent::find($request->event_id);
            $eventName = $event?->name ?? $request->event_id;
        }

        $centerName = 'All Centers';
        $user = Auth::user();
        if ($user->isEvacPersonnel() && $user->assigned_center_id) {
            $center = EvacuationCenter::find($user->assigned_center_id);
            $centerName = $center?->name ?? 'Assigned Center';
        } elseif ($request->filled('center_id') && $request->center_id !== 'all') {
            $center = EvacuationCenter::find($request->center_id);
            $centerName = $center?->name ?? $request->center_id;
        }

        return [
            'event' => $eventName,
            'center' => $centerName,
            'start_date' => $request->input('start_date', 'N/A'),
            'end_date' => $request->input('end_date', 'N/A'),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'generated_by' => Auth::user()?->name ?? '',
        ];
    }

    private function respond(Request $request, array $headers, array $rows, string $filePrefix, string $pdfView, array $pdfData)
    {
        $format = $request->input('format', 'csv');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView($pdfView, $pdfData)
                ->setPaper('a4', 'landscape')
                ->setOption('isRemoteEnabled', true);

            $fileName = $filePrefix . '_' . now()->format('Y-m-d') . '.pdf';
            return $pdf->download($fileName);
        }

        $fileName = $filePrefix . '_' . now()->format('Y-m-d') . '.csv';
        return $this->streamCsv($fileName, $headers, $rows);
    }

    private function streamCsv(string $fileName, array $headers, array $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function sanitize(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', str_replace(' ', '_', $name));
    }

    private function getAgeGroup(?int $age): string
    {
        if ($age === null) return '';
        if ($age <= 12) return 'Children (0-12)';
        if ($age <= 17) return 'Youth (13-17)';
        if ($age <= 59) return 'Adults (18-59)';
        return 'Senior (60+)';
    }

    private function getGenderKey($m): string
    {
        $relation = $m->getRelation('gender');
        if ($relation instanceof \App\Models\Gender) {
            return $relation->gender_key;
        }

        $genderVal = $m->gender;
        if ($genderVal instanceof \App\Models\Gender) {
            return $genderVal->gender_key;
        }

        if (is_string($genderVal)) {
            return strtolower($genderVal);
        }

        return '';
    }

    private function getGenderLabel($m): string
    {
        $relation = $m->getRelation('gender');
        if ($relation instanceof \App\Models\Gender) {
            return $relation->gender_label;
        }

        $genderVal = $m->gender;
        if ($genderVal instanceof \App\Models\Gender) {
            return $genderVal->gender_label;
        }

        if (is_string($genderVal)) {
            return ucfirst($genderVal);
        }

        return '';
    }

    // ─── 1. DROMIC MASTER LIST ───

    #[OA\Get(
        path: '/analytics/export/dromic',
        summary: 'Export DROMIC Master List',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'event_id', in: 'query', description: 'Filter by event ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by center ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Start date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'End date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'format', in: 'query', description: 'Export format (csv, pdf)', required: false, schema: new OA\Schema(type: 'string', enum: ['csv', 'pdf'], default: 'csv'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function dromicMasterList(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        $meta = $this->getFilterMeta($request);

        $query = EvacuationRecord::with([
            'household.address.barangay.city.province',
            'household.address.purok', 'household.address.sitio',
            'household.members.gender',
            'household.members.vulnerableGroupDetails',
            'evacuatedMembers.member',
            'unitAllocations.unit.type',
            'event', 'verifier', 'center',
        ])->where('household_status_id', 2);

        $this->applyFilters($request, $query);
        $records = $query->latest('verified_at')->get();

        $headers = [
            'No.','Disaster Event','Evacuation Center','Household ID','Family Name',
            'Contact Number','Home Address','Total Members','Male','Female',
            'Children (0-12)','Youth (13-17)','Adults (18-59)','Senior (60+)',
            'PWD','Pregnant','Indigenous','Allocated Unit','Date Admitted','Admitted By',
        ];

        $rows = [];
        $no = 1;
        foreach ($records as $record) {
            $hh = $record->household;
            $evacuatedMemberIds = $record->evacuatedMembers->pluck('member_id')->toArray();
            $members = $hh?->members?->filter(fn($m) => in_array($m->member_id, $evacuatedMemberIds)) ?? collect();

            $male = $female = $children = $youth = $adults = $elderly = $pwd = $pregnant = $indigenous = 0;
            foreach ($members as $m) {
                $gk = $this->getGenderKey($m);
                if ($gk === 'male') $male++;
                elseif ($gk === 'female') $female++;
                $age = $m->birth_date ? Carbon::parse($m->birth_date)->age : null;
                if ($age !== null) {
                    if ($age <= 12) $children++;
                    elseif ($age <= 17) $youth++;
                    elseif ($age <= 59) $adults++;
                    else $elderly++;
                }
                $vgKeys = $m->vulnerableGroupDetails->pluck('vulnerable_group_key')->toArray();
                if (in_array('pwd', $vgKeys)) $pwd++;
                if (in_array('pregnant', $vgKeys)) $pregnant++;
                if (in_array('indigenous', $vgKeys)) $indigenous++;
            }

            $unit = $record->unitAllocations->first();
            $rows[] = [
                $no++,
                $record->event?->name ?? '',
                $record->center?->name ?? '',
                $record->household_id,
                $hh?->household_name ?? '',
                $hh?->contact_number ?? '',
                $hh?->address ? $hh->address->full_address : '',
                $record->evacuated_count ?? 0,
                $male, $female, $children, $youth, $adults, $elderly,
                $pwd, $pregnant, $indigenous,
                $unit?->unit?->name ?? 'Unassigned',
                $record->verified_at ? Carbon::parse($record->verified_at)->format('Y-m-d H:i') : '',
                $record->verifier?->name ?? '',
            ];
        }

        return $this->respond($request, $headers, $rows, 'DROMIC_MasterList', 'exports.dromic', [
            'meta' => $meta, 'headers' => $headers, 'rows' => $rows, 'title' => 'DROMIC Master List of Evacuees',
        ]);
    }

    // ─── 2. DEMOGRAPHIC SUMMARY ───

    #[OA\Get(
        path: '/analytics/export/demographics',
        summary: 'Export Demographic Summary',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'event_id', in: 'query', description: 'Filter by event ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by center ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Start date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'End date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'format', in: 'query', description: 'Export format (csv, pdf)', required: false, schema: new OA\Schema(type: 'string', enum: ['csv', 'pdf'], default: 'csv'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function demographicSummary(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        $meta = $this->getFilterMeta($request);

        $query = EvacuationRecord::where('household_status_id', 2);
        $this->applyFilters($request, $query);
        $evacuationIds = $query->pluck('evacuation_id');

        $members = EvacuatedMember::with(['member.gender', 'member.vulnerableGroupDetails'])
            ->whereIn('evacuation_id', $evacuationIds)->get();

        $totalHouseholds = $query->distinct('household_id')->count('household_id');
        $totalIndividuals = $members->count();
        $male = $female = $children = $youth = $adults = $elderly = 0;
        $vulnCounts = [];

        foreach ($members as $em) {
            $m = $em->member;
            if (!$m) continue;
            $gk = $this->getGenderKey($m);
            if ($gk === 'male') $male++;
            elseif ($gk === 'female') $female++;
            $age = $m->birth_date ? Carbon::parse($m->birth_date)->age : null;
            if ($age !== null) {
                if ($age <= 12) $children++;
                elseif ($age <= 17) $youth++;
                elseif ($age <= 59) $adults++;
                else $elderly++;
            }
            foreach ($m->vulnerableGroupDetails as $vg) {
                $vulnCounts[$vg->vulnerable_group_label] = ($vulnCounts[$vg->vulnerable_group_label] ?? 0) + 1;
            }
        }

        $headers = ['Metric', 'Count', 'Percentage'];
        $rows = [
            ['POPULATION SUMMARY', '', ''],
            ['Total Households', $totalHouseholds, ''],
            ['Total Individuals', $totalIndividuals, '100%'],
            ['', '', ''],
            ['GENDER BREAKDOWN', '', ''],
            ['Male', $male, $totalIndividuals > 0 ? round(($male / $totalIndividuals) * 100, 1) . '%' : '0%'],
            ['Female', $female, $totalIndividuals > 0 ? round(($female / $totalIndividuals) * 100, 1) . '%' : '0%'],
            ['', '', ''],
            ['AGE DISTRIBUTION', '', ''],
            ['Children (0-12)', $children, $totalIndividuals > 0 ? round(($children / $totalIndividuals) * 100, 1) . '%' : '0%'],
            ['Youth (13-17)', $youth, $totalIndividuals > 0 ? round(($youth / $totalIndividuals) * 100, 1) . '%' : '0%'],
            ['Adults (18-59)', $adults, $totalIndividuals > 0 ? round(($adults / $totalIndividuals) * 100, 1) . '%' : '0%'],
            ['Senior Citizens (60+)', $elderly, $totalIndividuals > 0 ? round(($elderly / $totalIndividuals) * 100, 1) . '%' : '0%'],
            ['', '', ''],
            ['VULNERABLE GROUPS', '', ''],
        ];
        foreach ($vulnCounts as $label => $count) {
            $rows[] = [$label, $count, $totalIndividuals > 0 ? round(($count / $totalIndividuals) * 100, 1) . '%' : '0%'];
        }

        $pdfData = compact('meta', 'totalHouseholds', 'totalIndividuals', 'male', 'female', 'children', 'youth', 'adults', 'elderly', 'vulnCounts');
        $pdfData['title'] = 'Demographic Summary Report';

        return $this->respond($request, $headers, $rows, 'Demographic_Summary', 'exports.demographic', $pdfData);
    }

    // ─── 3. CENTER UTILIZATION ───

    #[OA\Get(
        path: '/analytics/export/utilization',
        summary: 'Export Center Utilization Summary',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'event_id', in: 'query', description: 'Filter by event ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by center ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Start date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'End date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'format', in: 'query', description: 'Export format (csv, pdf)', required: false, schema: new OA\Schema(type: 'string', enum: ['csv', 'pdf'], default: 'csv'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function centerUtilization(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        $meta = $this->getFilterMeta($request);

        $query = EvacuationRecord::where('household_status_id', 2);
        $this->applyFilters($request, $query);

        $records = $query->get();
        $centerIds = $records->pluck('center_id')->unique();
        $centers = EvacuationCenter::with('accommodationUnits.unitAllocations')
            ->whereIn('evacuation_center_id', $centerIds)->get();

        $headers = [
            'Evacuation Center','Address','Total Capacity','Current Occupants','Available Slots',
            'Utilization %','Total Households','Total Units','Occupied Units','Available Units','Status',
        ];

        $rows = [];
        foreach ($centers as $center) {
            $centerRecords = $records->where('center_id', $center->evacuation_center_id);
            $occupants = (int) $centerRecords->sum('evacuated_count');
            $households = $centerRecords->pluck('household_id')->unique()->count();
            $capacity = (int) $center->capacity;
            $available = max(0, $capacity - $occupants);
            $pct = $capacity > 0 ? round(($occupants / $capacity) * 100, 1) : 0;
            $totalUnits = $center->accommodationUnits->count();
            $occupiedUnits = $center->accommodationUnits->filter(fn($u) => $u->unitAllocations->count() > 0)->count();

            $rows[] = [
                $center->name, $center->osm_address, $capacity, $occupants, $available,
                $pct . '%', $households, $totalUnits, $occupiedUnits, $totalUnits - $occupiedUnits,
                $pct >= 90 ? 'Overcapacity' : ($pct >= 70 ? 'Near Capacity' : 'Optimal'),
            ];
        }

        return $this->respond($request, $headers, $rows, 'Center_Utilization', 'exports.center-utilization', [
            'meta' => $meta, 'headers' => $headers, 'rows' => $rows, 'title' => 'Center Utilization & Capacity Report',
        ]);
    }

    // ─── 4. VULNERABLE GROUPS CARE LIST ───

    #[OA\Get(
        path: '/analytics/export/vulnerable',
        summary: 'Export Vulnerable Groups Care List',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'event_id', in: 'query', description: 'Filter by event ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by center ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Start date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'End date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'format', in: 'query', description: 'Export format (csv, pdf)', required: false, schema: new OA\Schema(type: 'string', enum: ['csv', 'pdf'], default: 'csv'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function vulnerableGroups(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        $meta = $this->getFilterMeta($request);

        $query = EvacuationRecord::with([
            'household.address.barangay.city.province',
            'household.members.gender', 'household.members.vulnerableGroupDetails',
            'evacuatedMembers', 'unitAllocations.unit', 'center',
        ])->where('household_status_id', 2);
        $this->applyFilters($request, $query);
        $records = $query->get();

        $headers = [
            'Evacuation Center','Household Name','Member Name','Age','Gender',
            'Vulnerability Type','Contact Number','Allocated Unit','Home Address',
        ];

        $rows = [];
        foreach ($records as $record) {
            $hh = $record->household;
            $evacuatedIds = $record->evacuatedMembers->pluck('member_id')->toArray();
            $unit = $record->unitAllocations->first();

            foreach ($hh?->members ?? [] as $m) {
                if (!in_array($m->member_id, $evacuatedIds)) continue;
                $vgLabels = $m->vulnerableGroupDetails->pluck('vulnerable_group_label')->toArray();
                if (empty($vgLabels)) continue;

                $age = $m->birth_date ? Carbon::parse($m->birth_date)->age : null;
                if ($age !== null && $age >= 60 && !in_array('Elderly', $vgLabels)) {
                    $vgLabels[] = 'Elderly';
                }

                $rows[] = [
                    $record->center?->name ?? '',
                    $hh->household_name ?? '',
                    trim(($m->first_name ?? '') . ' ' . ($m->middle_name ?? '') . ' ' . ($m->last_name ?? '')),
                    $age ?? '', $this->getGenderLabel($m),
                    implode(', ', $vgLabels),
                    $hh->contact_number ?? '',
                    $unit?->unit?->name ?? 'Unassigned',
                    $hh->address ? $hh->address->full_address : '',
                ];
            }
        }

        return $this->respond($request, $headers, $rows, 'Vulnerable_Groups', 'exports.vulnerable-groups', [
            'meta' => $meta, 'headers' => $headers, 'rows' => $rows, 'title' => 'Vulnerable Groups Care List',
        ]);
    }

    // ─── 5. RESOURCE REQUESTS ───

    #[OA\Get(
        path: '/analytics/export/resources',
        summary: 'Export Resource Requests Summary',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by center ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Start date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'End date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'format', in: 'query', description: 'Export format (csv, pdf)', required: false, schema: new OA\Schema(type: 'string', enum: ['csv', 'pdf'], default: 'csv'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function resourceRequests(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        $meta = $this->getFilterMeta($request);

        $query = ResourceRequest::with(['center', 'requester', 'handler', 'urgencyLevel', 'status']);

        $user = Auth::user();
        if ($user->isEvacPersonnel() && $user->assigned_center_id) {
            $query->where('evacuation_center_id', $user->assigned_center_id);
        } elseif ($request->filled('center_id') && $request->center_id !== 'all') {
            $query->where('evacuation_center_id', $request->center_id);
        }
        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->end_date);

        $items = $query->latest()->get();

        $headers = [
            'Request ID','Evacuation Center','Resource Type','Quantity','Description',
            'Urgency','Status','Requested By','Handled By','Date Requested','Date Updated',
        ];

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                $item->request_id,
                $item->center?->name ?? '',
                $item->resource_type ?? '',
                $item->quantity ?? '',
                $item->description ?? '',
                $item->urgencyLevel?->urgency_label ?? '',
                $item->status?->status_label ?? '',
                $item->requester?->name ?? '',
                $item->handler?->name ?? '',
                $item->created_at?->format('Y-m-d H:i') ?? '',
                $item->updated_at?->format('Y-m-d H:i') ?? '',
            ];
        }

        return $this->respond($request, $headers, $rows, 'Resource_Requests', 'exports.table-report', [
            'meta' => $meta, 'headers' => $headers, 'rows' => $rows, 'title' => 'Resource Requests Report',
        ]);
    }

    // ─── 6. CENTER ISSUES ───

    #[OA\Get(
        path: '/analytics/export/issues',
        summary: 'Export Center Issues Summary',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by center ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Start date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'End date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'format', in: 'query', description: 'Export format (csv, pdf)', required: false, schema: new OA\Schema(type: 'string', enum: ['csv', 'pdf'], default: 'csv'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function centerIssues(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        $meta = $this->getFilterMeta($request);

        $query = CenterIssueReport::with(['center', 'reporter', 'handler', 'category', 'severityLevel', 'status']);

        $user = Auth::user();
        if ($user->isEvacPersonnel() && $user->assigned_center_id) {
            $query->where('evacuation_center_id', $user->assigned_center_id);
        } elseif ($request->filled('center_id') && $request->center_id !== 'all') {
            $query->where('evacuation_center_id', $request->center_id);
        }
        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->end_date);

        $items = $query->latest()->get();

        $headers = [
            'Report ID','Evacuation Center','Category','Title','Description',
            'Severity','Status','Reported By','Handled By','Date Reported','Date Updated',
        ];

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                $item->report_id,
                $item->center?->name ?? '',
                $item->category?->category_label ?? '',
                $item->title ?? '',
                $item->description ?? '',
                $item->severityLevel?->severity_label ?? '',
                $item->status?->status_label ?? '',
                $item->reporter?->name ?? '',
                $item->handler?->name ?? '',
                $item->created_at?->format('Y-m-d H:i') ?? '',
                $item->updated_at?->format('Y-m-d H:i') ?? '',
            ];
        }

        return $this->respond($request, $headers, $rows, 'Center_Issues', 'exports.table-report', [
            'meta' => $meta, 'headers' => $headers, 'rows' => $rows, 'title' => 'Center Issue Reports',
        ]);
    }

    // ─── 7. DAILY INTAKE TRENDS (CSV only) ───

    #[OA\Get(
        path: '/analytics/export/daily-intake',
        summary: 'Export Daily Intake Trends (CSV only)',
        security: [['bearerAuth' => []]],
        tags: ['Analytics']
    )]
    #[OA\Parameter(name: 'event_id', in: 'query', description: 'Filter by event ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by center ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'start_date', in: 'query', description: 'Start date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', in: 'query', description: 'End date filter', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(
        response: 200, 
        description: 'CSV file download response',
        content: new OA\MediaType(
            mediaType: 'text/csv'
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function dailyIntake(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $query = EvacuationRecord::with('center');
        $this->applyFilters($request, $query);

        $records = $query->orderBy('created_at')->get();

        $headers = ['Date','Evacuation Center','New Households','New Individuals'];
        $rows = [];

        $grouped = $records->groupBy(function ($r) {
            return $r->created_at->format('Y-m-d') . '|' . $r->center_id;
        });

        foreach ($grouped as $key => $group) {
            [$date] = explode('|', $key);
            $rows[] = [
                $date,
                $group->first()->center?->name ?? '',
                $group->pluck('household_id')->unique()->count(),
                (int) $group->sum('evacuated_count'),
            ];
        }

        $fileName = 'Daily_Intake_' . now()->format('Y-m-d') . '.csv';
        return $this->streamCsv($fileName, $headers, $rows);
    }
}

