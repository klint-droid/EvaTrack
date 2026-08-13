<?php

namespace App\Domains\CenterIssueReports\Repositories;

use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use App\Domains\CenterIssueReports\Models\CenterIssueReportStatus;
use App\Domains\CenterIssueReports\Models\CenterIssueCategory;
use App\Domains\EvacuationEvents\Models\SeverityLevel;
use App\Domains\CenterIssueReports\DTOs\CenterIssueReportFilterDTO;
use Illuminate\Support\Facades\Storage;
use Exception;

class EloquentCenterIssueReportRepository implements CenterIssueReportRepositoryInterface
{
    private function reportRelations(): array
    {
        return [
            'center',
            'reporter',
            'handler',
            'category',
            'severityLevel',
            'status',
        ];
    }

    public function getFilteredReports(CenterIssueReportFilterDTO $filter, int|string|null $enforcedCenterId = null): array
    {
        $query = CenterIssueReport::with($this->reportRelations());

        if ($enforcedCenterId !== null) {
            $query->where('evacuation_center_id', $enforcedCenterId);
        } elseif ($filter->centerId !== null) {
            $query->where('evacuation_center_id', $filter->centerId);
        }

        if ($filter->category !== null) {
            $query->whereHas('category', function ($q) use ($filter) {
                $q->where('category_key', $filter->category);
            });
        }

        if ($filter->severity !== null) {
            $query->whereHas('severityLevel', function ($q) use ($filter) {
                $q->where('severity_key', $filter->severity);
            });
        }

        if ($filter->status !== null) {
            $query->whereHas('status', function ($q) use ($filter) {
                $q->where('status_key', $filter->status);
            });
        }

        if ($filter->q !== null) {
            $search = $filter->q;
            $query->where(function ($q) use ($search) {
                $q->where('report_id', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $openStatusId = CenterIssueReportStatus::where('status_key', 'open')->value('status_id');
        $inProgressStatusId = CenterIssueReportStatus::where('status_key', 'in_progress')->value('status_id');
        $resolvedStatusId = CenterIssueReportStatus::where('status_key', 'resolved')->value('status_id');
        $criticalSeverityId = SeverityLevel::where('severity_key', 'critical')->value('severity_id');
        $highSeverityId = SeverityLevel::where('severity_key', 'high')->value('severity_id');
        $mediumSeverityId = SeverityLevel::where('severity_key', 'medium')->value('severity_id');
        $lowSeverityId = SeverityLevel::where('severity_key', 'low')->value('severity_id');

        $summary = [
            'open'        => $openStatusId ? (clone $query)->where('status_id', $openStatusId)->count() : 0,
            'in_progress' => $inProgressStatusId ? (clone $query)->where('status_id', $inProgressStatusId)->count() : 0,
            'resolved'    => $resolvedStatusId ? (clone $query)->where('status_id', $resolvedStatusId)->count() : 0,
            'critical'    => $criticalSeverityId ? (clone $query)->where('severity_id', $criticalSeverityId)->count() : 0,
            'high'        => $highSeverityId ? (clone $query)->where('severity_id', $highSeverityId)->count() : 0,
            'medium'      => $mediumSeverityId ? (clone $query)->where('severity_id', $mediumSeverityId)->count() : 0,
            'low'         => $lowSeverityId ? (clone $query)->where('severity_id', $lowSeverityId)->count() : 0,
        ];

        if ($filter->limit > 0) {
            $query->limit($filter->limit);
        }

        return [
            'data'    => $query->latest('created_at')->get(),
            'summary' => $summary,
        ];
    }

    public function getReportById(string $id, int|string|null $enforcedCenterId = null): ?CenterIssueReport
    {
        $query = CenterIssueReport::with($this->reportRelations())->where('report_id', $id);

        if ($enforcedCenterId !== null) {
            $query->where('evacuation_center_id', $enforcedCenterId);
        }

        return $query->first();
    }

    public function createReport(array $data): CenterIssueReport
    {
        if (isset($data['category'])) {
            $data['category_id'] = CenterIssueCategory::where('category_key', $data['category'])->value('category_id');
            unset($data['category']);
        }

        if (isset($data['severity'])) {
            $data['severity_id'] = SeverityLevel::where('severity_key', $data['severity'])->value('severity_id');
            unset($data['severity']);
        }

        $data['status_id'] = CenterIssueReportStatus::where('status_key', 'open')->value('status_id');
        $data['handled_by'] = null;

        $report = CenterIssueReport::create($data);

        return $report->load($this->reportRelations());
    }

    public function updateReport(CenterIssueReport $report, array $data): CenterIssueReport
    {
        if (isset($data['category'])) {
            $data['category_id'] = CenterIssueCategory::where('category_key', $data['category'])->value('category_id');
            unset($data['category']);
        }

        if (isset($data['severity'])) {
            $data['severity_id'] = SeverityLevel::where('severity_key', $data['severity'])->value('severity_id');
            unset($data['severity']);
        }

        if (isset($data['attachment_path']) && $report->attachment_path) {
            Storage::disk('public')->delete($report->attachment_path);
        }

        $report->update($data);

        return $report->fresh($this->reportRelations());
    }

    public function updateStatus(CenterIssueReport $report, string $statusKey, int $handlerUserId): CenterIssueReport
    {
        $statusId = CenterIssueReportStatus::where('status_key', $statusKey)->value('status_id');
        
        if (!$statusId) {
            throw new Exception("The selected status is invalid.");
        }

        $report->update([
            'status_id'  => $statusId,
            'handled_by' => $handlerUserId,
        ]);

        return $report->fresh($this->reportRelations());
    }

    public function deleteReport(CenterIssueReport $report): void
    {
        if ($report->attachment_path) {
            Storage::disk('public')->delete($report->attachment_path);
        }
        $report->delete();
    }
}
