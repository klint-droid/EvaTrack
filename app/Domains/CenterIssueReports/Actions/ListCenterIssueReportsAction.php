<?php

namespace App\Domains\CenterIssueReports\Actions;

use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\CenterIssueReports\DTOs\CenterIssueReportFilterDTO;

class ListCenterIssueReportsAction
{
    public function __construct(
        private CenterIssueReportRepositoryInterface $repository
    ) {}

    public function execute(CenterIssueReportFilterDTO $filter, int|string|null $enforcedCenterId = null): array
    {
        return $this->repository->getFilteredReports($filter, $enforcedCenterId);
    }
}
