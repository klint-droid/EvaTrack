<?php

namespace App\Domains\CenterIssueReports\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Notifications\Models\UserNotification;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateCenterIssueReportStatusAction
{
    public function __construct(
        private CenterIssueReportRepositoryInterface $repository
    ) {}

    public function execute(string $id, string $statusKey, int|string $handlerUserId): CenterIssueReport
    {
        $report = $this->repository->getReportById($id);
        
        if (!$report) {
            throw new Exception("Report not found.", 404);
        }

        $updated = $this->repository->updateStatus($report, $statusKey, $handlerUserId);

        // Dispatch in-app notification to the original reporter
        $this->notifyReporter($updated, $statusKey, (string) $handlerUserId);

        return $updated;
    }

    /**
     * Dispatch in-app notification (user_notifications) to the reporting staff member
     * when their center issue report status changes.
     */
    protected function notifyReporter(CenterIssueReport $report, string $statusKey, string $handlerUserId): void
    {
        try {
            $reporterId = $report->reported_by;
            if (empty($reporterId)) {
                return;
            }

            // Avoid self-notification if handler is the reporter
            if ((string) $reporterId === (string) $handlerUserId) {
                return;
            }

            $handler = User::find($handlerUserId);
            $handlerName = $handler ? $handler->name : 'An administrator';

            $statusModel = $report->status;
            $statusLabel = $statusModel ? $statusModel->status_label : ucfirst(str_replace('_', ' ', $statusKey));

            $center = $report->center ?: EvacuationCenter::find($report->evacuation_center_id);
            $centerName = $center ? $center->name : 'your evacuation center';
            $issueTitle = trim((string) $report->title) ?: 'Reported Issue';

            $title = "Center Issue {$statusLabel}";
            $message = "Your reported issue \"{$issueTitle}\" at {$centerName} has been marked as {$statusLabel} by {$handlerName}.";

            $data = [
                'report_id'    => (string) $report->report_id,
                'center_id'    => (string) $report->evacuation_center_id,
                'center_name'  => $centerName,
                'title'        => $issueTitle,
                'status'       => $statusKey,
                'status_label' => $statusLabel,
                'handled_by'   => $handlerName,
                'link'         => '/center-issue-reports',
            ];

            UserNotification::create([
                'user_id' => $reporterId,
                'type'    => 'center_issue_status',
                'title'   => $title,
                'message' => $message,
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('[UpdateCenterIssueReportStatusAction] Failed to dispatch reporter notification: ' . $e->getMessage(), [
                'report_id' => $report->report_id ?? null,
                'status'    => $statusKey,
            ]);
        }
    }
}
