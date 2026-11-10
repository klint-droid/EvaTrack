<?php

namespace App\Domains\CenterIssueReports\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\CenterIssueReports\DTOs\CenterIssueReportDTO;
use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Notifications\Models\UserNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CreateCenterIssueReportAction
{
    public function __construct(
        private CenterIssueReportRepositoryInterface $repository
    ) {}

    public function execute(CenterIssueReportDTO $dto, int|string $reporterUserId, int|string|null $enforcedCenterId = null, ?UploadedFile $attachment = null): CenterIssueReport
    {
        $data = $dto->toArray();
        $data['reported_by'] = $reporterUserId;
        
        if ($enforcedCenterId !== null) {
            $data['evacuation_center_id'] = $enforcedCenterId;
        }

        if ($attachment) {
            $data['attachment_path'] = $attachment->store('center_issues', 'public');
        } else {
            $data['attachment_path'] = null;
        }

        $report = $this->repository->createReport($data);

        // Dispatch in-app notifications to Administrators
        $this->notifyAdministrators($report, (string) $reporterUserId);

        return $report;
    }

    /**
     * Dispatch in-app notifications (user_notifications) to all relevant administrators
     * when a new center issue report is submitted.
     */
    protected function notifyAdministrators(CenterIssueReport $report, string $reporterUserId): void
    {
        try {
            $reporter = User::find($reporterUserId);
            $reporterName = $reporter ? $reporter->name : 'A staff member';

            $center = $report->center ?: EvacuationCenter::find($report->evacuation_center_id);
            $centerName = $center ? $center->name : 'an evacuation center';

            $categoryName = $report->category?->category_name ?? 'General Issue';
            $severityName = $report->severityLevel?->severity_name ?? 'Medium';
            $issueTitle = trim((string) $report->title) ?: 'Untitled Issue';

            // Active Super Admins + Evacuation Admins associated with this center (or system-wide)
            $admins = User::query()
                ->where('is_active', true)
                ->where('user_id', '!=', $reporterUserId)
                ->where(function ($q) use ($report) {
                    $q->where('role_id', User::ROLE_SUPER_ADMIN_ID)
                      ->orWhere(function ($eq) use ($report) {
                          $eq->where('role_id', User::ROLE_EVAC_ADMIN_ID)
                             ->where(function ($cq) use ($report) {
                                 $cq->where('assigned_center_id', $report->evacuation_center_id)
                                    ->orWhereNull('assigned_center_id');
                             });
                      });
                })
                ->get();

            $title = 'New Center Issue Reported';
            $message = "{$reporterName} reported a [{$severityName}] issue: \"{$issueTitle}\" at {$centerName}.";
            $data = [
                'report_id'    => (string) $report->report_id,
                'center_id'    => (string) $report->evacuation_center_id,
                'center_name'  => $centerName,
                'title'        => $issueTitle,
                'category'     => $categoryName,
                'severity'     => $severityName,
                'reported_by'  => $reporterName,
                'link'         => '/center-issue-reports',
            ];

            foreach ($admins as $admin) {
                UserNotification::create([
                    'user_id' => $admin->user_id,
                    'type'    => 'center_issue_created',
                    'title'   => $title,
                    'message' => $message,
                    'data'    => $data,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[CreateCenterIssueReportAction] Failed to dispatch admin notifications: ' . $e->getMessage(), [
                'report_id' => $report->report_id ?? null,
            ]);
        }
    }
}
