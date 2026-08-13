<?php

namespace App\Domains\EvacuationCenters\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use App\Domains\ResourceRequests\Models\ResourceRequest;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StatusUpdateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $centerId = $request->query('center_id');
        $category = $request->query('category');

        $updates = collect();

        // 1. Fetch Issue Reports
        $issueQuery = CenterIssueReport::with(['center', 'reporter']);
        if ($centerId) {
            $issueQuery->where('evacuation_center_id', $centerId);
        }
        $issues = $issueQuery->latest()->take(20)->get()->map(function ($issue) {
            $statusLabel = $this->formatStatus($issue->status);
            return [
                'id' => 'issue-' . $issue->issue_id,
                'type' => 'issue',
                'category' => 'Incident Reports',
                'title' => $issue->title ?? 'Issue Report',
                'description' => $issue->description ?? '',
                'status' => $statusLabel,
                'severity' => strtolower($statusLabel) === 'critical' || strtolower($statusLabel) === 'high' ? 'danger' : 'warning',
                'center_name' => $issue->center->name ?? 'Evacuation Center',
                'author' => $issue->reporter->name ?? 'Staff',
                'author_initials' => $this->getInitials($issue->reporter->name ?? 'Staff'),
                'created_at' => $issue->created_at ? $issue->created_at->toIso8601String() : now()->toIso8601String(),
                'relative_time' => $issue->created_at ? $issue->created_at->diffForHumans() : 'Just now',
            ];
        });

        // 2. Fetch Resource Requests
        $reqQuery = ResourceRequest::with(['center', 'requester']);
        if ($centerId) {
            $reqQuery->where('center_id', $centerId);
        }
        $requests = $reqQuery->latest()->take(20)->get()->map(function ($req) {
            $statusLabel = $this->formatStatus($req->status);
            return [
                'id' => 'req-' . $req->request_id,
                'type' => 'resource',
                'category' => 'Relief Logistics',
                'title' => 'Resource Request: ' . ($req->item_name ?? 'Relief Supplies'),
                'description' => ($req->quantity ?? 1) . ' units requested. Note: ' . ($req->notes ?? 'Urgent allocation needed.'),
                'status' => $statusLabel,
                'severity' => strtolower($statusLabel) === 'approved' ? 'success' : 'warning',
                'center_name' => $req->center->name ?? 'Evacuation Center',
                'author' => $req->requester->name ?? 'Logistics Team',
                'author_initials' => $this->getInitials($req->requester->name ?? 'Logistics Team'),
                'created_at' => $req->created_at ? $req->created_at->toIso8601String() : now()->toIso8601String(),
                'relative_time' => $req->created_at ? $req->created_at->diffForHumans() : 'Just now',
            ];
        });

        // 3. Fetch Evacuation Centers
        $centerQuery = EvacuationCenter::query();
        if ($centerId) {
            $centerQuery->where('evacuation_center_id', $centerId);
        }
        $centers = $centerQuery->latest('updated_at')->take(20)->get()->map(function ($center) {
            $occ = (int) ($center->current_occupancy ?? 0);
            $cap = (int) ($center->capacity ?? 0);
            $statusStr = $this->formatStatus($center->status ?? 'active');

            return [
                'id' => 'center-' . $center->evacuation_center_id,
                'type' => 'evacuation',
                'category' => 'Evacuation Centers',
                'title' => 'Evacuation Center Status: ' . $center->name,
                'description' => 'Shelter current status: ' . $statusStr . '. Current Occupancy: ' . $occ . ' / ' . $cap . ' evacuees.',
                'status' => $statusStr,
                'severity' => strtolower($statusStr) === 'inactive' || strtolower($statusStr) === 'closed' ? 'danger' : 'success',
                'center_name' => $center->name ?? 'Evacuation Center',
                'author' => 'System Operations',
                'author_initials' => 'EC',
                'created_at' => $center->updated_at ? $center->updated_at->toIso8601String() : now()->toIso8601String(),
                'relative_time' => $center->updated_at ? $center->updated_at->diffForHumans() : 'Just now',
            ];
        });

        // Combine and sort by created_at DESC
        $updates = $updates->concat($issues)->concat($requests)->concat($centers)->sortByDesc('created_at')->values();

        if ($category && $category !== 'all') {
            $updates = $updates->filter(function ($item) use ($category) {
                return strtolower($item['category']) === strtolower($category) || strtolower($item['type']) === strtolower($category);
            })->values();
        }

        return response()->json([
            'status' => 'success',
            'data'   => $updates,
        ]);
    }

    private function formatStatus($status): string
    {
        if (!$status) return 'Reported';
        if (is_array($status)) {
            return ucfirst($status['status_label'] ?? $status['status_key'] ?? $status['name'] ?? 'Reported');
        }
        if (is_object($status)) {
            return ucfirst($status->status_label ?? $status->status_key ?? $status->name ?? 'Reported');
        }
        if (is_string($status) && str_starts_with(trim($status), '{')) {
            $decoded = json_decode($status, true);
            if (is_array($decoded)) {
                return ucfirst($decoded['status_label'] ?? $decoded['status_key'] ?? $decoded['name'] ?? $status);
            }
        }
        return ucfirst((string) $status);
    }

    private function getInitials(?string $name): string
    {
        if (!$name) return 'EV';
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= strtoupper($w[0]);
            }
        }
        return substr($initials, 0, 2) ?: 'EV';
    }
}
