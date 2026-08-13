<?php

namespace App\Domains\CenterIssueReports\Actions;

use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\Authentication\Models\User;
use Exception;

class DeleteCenterIssueReportAction
{
    public function __construct(
        private CenterIssueReportRepositoryInterface $repository
    ) {}

    public function execute(string $id, User $user, int|string|null $enforcedCenterId = null): void
    {
        $report = $this->repository->getReportById($id, $enforcedCenterId);
        
        if (!$report) {
            throw new Exception("Report not found or unauthorized.", 404);
        }

        if ($user->isEvacPersonnel()) {
            if ($report->reported_by !== $user->user_id) {
                throw new Exception("You can only delete your own report.", 403);
            }

            if ($report->status && $report->status->status_key !== 'open') {
                throw new Exception("Only open reports can be deleted.", 400);
            }
        }

        $this->repository->deleteReport($report);
    }
}
