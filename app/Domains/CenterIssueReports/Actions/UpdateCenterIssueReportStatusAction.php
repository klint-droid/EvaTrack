<?php

namespace App\Domains\CenterIssueReports\Actions;

use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use Exception;

class UpdateCenterIssueReportStatusAction
{
    public function __construct(
        private CenterIssueReportRepositoryInterface $repository
    ) {}

    public function execute(string $id, string $statusKey, int $handlerUserId): CenterIssueReport
    {
        $report = $this->repository->getReportById($id);
        
        if (!$report) {
            throw new Exception("Report not found.", 404);
        }

        return $this->repository->updateStatus($report, $statusKey, $handlerUserId);
    }
}
