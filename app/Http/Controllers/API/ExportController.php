<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\EvacuationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class ExportController extends Controller
{
    /**
     * Export evacuated household data for a given center as CSV.
     *
     * Query params:
     *   - type: 'household' | 'member' (default: 'member')
     */
    #[OA\Get(
        path: '/evacuation-centers/{center}/export',
        summary: 'Export evacuated household data for a given center as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Evacuation Centers']
    )]
    #[OA\Parameter(name: 'center', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'type', in: 'query', description: 'Export granularity type', required: false, schema: new OA\Schema(type: 'string', enum: ['household', 'member'], default: 'member'))]
    #[OA\Response(
        response: 200, 
        description: 'CSV file download response',
        content: new OA\MediaType(
            mediaType: 'text/csv'
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Center not found')]
    public function exportCenterHouseholds(Request $request, $centerId)
    {
        $user = Auth::user();

        // Authorization: only admins and personnel assigned to this center
        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id || $user->assigned_center_id !== $centerId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif (!$user->isSuperAdmin() && !$user->isEvacAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

        $exportType = $request->input('type', 'member');

        // Eager-load all relationships needed for both export types
        $records = EvacuationRecord::with([
            'household.address.barangay.city.province',
            'household.address.purok',
            'household.address.sitio',
            'household.address.zipcode',
            'household.members.gender',
            'household.members.civilStatus',
            'household.members.relationship',
            'household.members.vulnerableGroupDetails',
            'evacuatedMembers',
            'unitAllocations.unit.type',
            'event',
            'verifier',
        ])
            ->where('center_id', $centerId)
            ->where('household_status_id', 2) // Only evacuated
            ->latest('verified_at')
            ->get();

        $fileName = $this->sanitizeFileName($center->name) . '_Evacuees_' . now()->format('Y-m-d') . '.csv';

        if ($exportType === 'household') {
            return $this->streamHouseholdCsv($records, $fileName, $center);
        }

        return $this->streamMemberCsv($records, $fileName, $center);
    }

    /**
     * Stream household-level CSV (1 row per household).
     */
    private function streamHouseholdCsv($records, $fileName, $center): StreamedResponse
    {
        $headers = [
            'Evacuation Center',
            'Center Address',
            'Household ID',
            'Household Name',
            'Contact Number',
            'Emergency Contact',
            'Home Address',
            'Evacuation Status',
            'Evacuated Count',
            'Allocated Unit',
            'Unit Type',
            'Admission Method',
            'Verified By',
            'Verified At',
            'Disaster Event',
            'Export Date',
        ];

        return $this->streamCsv($fileName, $headers, function ($handle) use ($records, $center) {
            foreach ($records as $record) {
                $household = $record->household;
                $unitAllocation = $record->unitAllocations->first();

                fputcsv($handle, [
                    $center->name,
                    $center->osm_address,
                    $record->household_id,
                    $household?->household_name ?? '',
                    $household?->contact_number ?? '',
                    $household?->emergency_contact ?? '',
                    $household?->address ? $household->address->full_address : '',
                    'Evacuated',
                    $record->evacuated_count ?? 0,
                    $unitAllocation?->unit?->name ?? 'Unassigned',
                    $unitAllocation?->unit?->type?->type_label ?? '',
                    ucfirst($record->method ?? 'manual'),
                    $record->verifier?->name ?? '',
                    $record->verified_at ? Carbon::parse($record->verified_at)->format('Y-m-d H:i:s') : '',
                    $record->event?->name ?? '',
                    now()->format('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    /**
     * Stream member-level CSV (1 row per member).
     */
    private function streamMemberCsv($records, $fileName, $center): StreamedResponse
    {
        $headers = [
            'Evacuation Center',
            'Center Address',
            'Household ID',
            'Household Name',
            'Contact Number',
            'Emergency Contact',
            'Home Address',
            'Member ID',
            'First Name',
            'Middle Name',
            'Last Name',
            'Birth Date',
            'Age',
            'Age Group',
            'Gender',
            'Civil Status',
            'Relationship to Head',
            'Vulnerable Groups',
            'Is PWD',
            'Is Pregnant',
            'Is Senior Citizen',
            'Is Indigenous',
            'Member Evacuation Status',
            'Allocated Unit',
            'Unit Type',
            'Admission Method',
            'Verified By',
            'Verified At',
            'Disaster Event',
            'Export Date',
        ];

        return $this->streamCsv($fileName, $headers, function ($handle) use ($records, $center) {
            foreach ($records as $record) {
                $household = $record->household;
                $unitAllocation = $record->unitAllocations->first();

                // Get the list of evacuated member IDs for this record
                $evacuatedMemberIds = $record->evacuatedMembers->pluck('member_id')->toArray();

                $members = $household?->members ?? collect();

                if ($members->isEmpty()) {
                    // Still output the household row even if no members
                    fputcsv($handle, [
                        $center->name,
                        $center->osm_address,
                        $record->household_id,
                        $household?->household_name ?? '',
                        $household?->contact_number ?? '',
                        $household?->emergency_contact ?? '',
                        $household?->address ? $household->address->full_address : '',
                        '', '', '', '', '', '', '',
                        '', '', '',
                        '', '', '', '',
                        '',
                        $unitAllocation?->unit?->name ?? 'Unassigned',
                        $unitAllocation?->unit?->type?->type_label ?? '',
                        ucfirst($record->method ?? 'manual'),
                        $record->verifier?->name ?? '',
                        $record->verified_at ? Carbon::parse($record->verified_at)->format('Y-m-d H:i:s') : '',
                        $record->event?->name ?? '',
                        now()->format('Y-m-d H:i:s'),
                    ]);
                    continue;
                }

                foreach ($members as $member) {
                    $age = $member->birth_date ? Carbon::parse($member->birth_date)->age : null;
                    $ageGroup = $this->getAgeGroup($age);

                    $vulnerableGroupKeys = $member->vulnerableGroupDetails
                        ->pluck('vulnerable_group_key')
                        ->toArray();

                    $vulnerableGroupLabels = $member->vulnerableGroupDetails
                        ->pluck('vulnerable_group_label')
                        ->implode(', ');

                    $isEvacuated = in_array($member->member_id, $evacuatedMemberIds);

                    fputcsv($handle, [
                        $center->name,
                        $center->osm_address,
                        $record->household_id,
                        $household->household_name ?? '',
                        $household->contact_number ?? '',
                        $household->emergency_contact ?? '',
                        $household->address ? $household->address->full_address : '',
                        $member->member_id,
                        $member->first_name ?? '',
                        $member->middle_name ?? '',
                        $member->last_name ?? '',
                        $member->birth_date ? Carbon::parse($member->birth_date)->format('Y-m-d') : '',
                        $age ?? '',
                        $ageGroup,
                        $member->gender?->gender_label ?? '',
                        $member->civilStatus?->status_label ?? '',
                        $member->relationship?->relationship_label ?? '',
                        $vulnerableGroupLabels,
                        in_array('pwd', $vulnerableGroupKeys) ? 'Yes' : 'No',
                        in_array('pregnant', $vulnerableGroupKeys) ? 'Yes' : 'No',
                        in_array('elderly', $vulnerableGroupKeys) || ($age !== null && $age >= 60) ? 'Yes' : 'No',
                        in_array('indigenous', $vulnerableGroupKeys) ? 'Yes' : 'No',
                        $isEvacuated ? 'Evacuated' : 'Not Evacuated',
                        $unitAllocation?->unit?->name ?? 'Unassigned',
                        $unitAllocation?->unit?->type?->type_label ?? '',
                        ucfirst($record->method ?? 'manual'),
                        $record->verifier?->name ?? '',
                        $record->verified_at ? Carbon::parse($record->verified_at)->format('Y-m-d H:i:s') : '',
                        $record->event?->name ?? '',
                        now()->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        });
    }

    /**
     * Classify age into standard DROMIC age groups.
     */
    private function getAgeGroup(?int $age): string
    {
        if ($age === null) return '';
        if ($age < 1) return 'Infant (< 1)';
        if ($age <= 5) return 'Child (1-5)';
        if ($age <= 11) return 'Child (6-11)';
        if ($age <= 17) return 'Teen (12-17)';
        if ($age <= 59) return 'Adult (18-59)';
        return 'Senior (60+)';
    }

    /**
     * Create a streamed CSV response.
     */
    private function streamCsv(string $fileName, array $headers, callable $writeRows): StreamedResponse
    {
        return new StreamedResponse(function () use ($headers, $writeRows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write header row
            fputcsv($handle, $headers);

            // Write data rows
            $writeRows($handle);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Sanitize a file name by removing special characters.
     */
    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', str_replace(' ', '_', $name));
    }
}

