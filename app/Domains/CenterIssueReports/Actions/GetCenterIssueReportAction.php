<?php

namespace App\Domains\CenterIssueReports\Actions;

use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\CenterIssueReports\Models\CenterIssueReport;

class GetCenterIssueReportAction
{
    public function __construct(
        private CenterIssueReportRepositoryInterface $repository
    ) {}

    public function execute(string $id, ?int $enforcedCenterId = null): ?CenterIssueReport
    {
        return $this->repository->getReportById($id, $enforcedCenterId);
    }
}
