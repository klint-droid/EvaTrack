<?php

namespace App\Domains\CenterIssueReports\Repositories;

use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use App\Domains\CenterIssueReports\DTOs\CenterIssueReportFilterDTO;
use Illuminate\Database\Eloquent\Collection;

interface CenterIssueReportRepositoryInterface
{
    /**
     * Get a paginated or limited list of reports with summary stats.
     * 
     * @param CenterIssueReportFilterDTO $filter
     * @param int|null $enforcedCenterId If set, scopes the query to this center.
     * @return array ['data' => Collection, 'summary' => array]
     */
    public function getFilteredReports(CenterIssueReportFilterDTO $filter, ?int $enforcedCenterId = null): array;

    public function getReportById(string $id, ?int $enforcedCenterId = null): ?CenterIssueReport;

    public function createReport(array $data): CenterIssueReport;

    public function updateReport(CenterIssueReport $report, array $data): CenterIssueReport;

    public function updateStatus(CenterIssueReport $report, string $statusKey, int $handlerUserId): CenterIssueReport;

    public function deleteReport(CenterIssueReport $report): void;
}
