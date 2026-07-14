<?php

namespace App\Domains\CenterIssueReports\Actions;

use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\CenterIssueReports\DTOs\CenterIssueReportDTO;
use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use Illuminate\Http\UploadedFile;

class CreateCenterIssueReportAction
{
    public function __construct(
        private CenterIssueReportRepositoryInterface $repository
    ) {}

    public function execute(CenterIssueReportDTO $dto, int $reporterUserId, ?int $enforcedCenterId = null, ?UploadedFile $attachment = null): CenterIssueReport
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

        return $this->repository->createReport($data);
    }
}
