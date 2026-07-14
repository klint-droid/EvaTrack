<?php

namespace App\Domains\CenterIssueReports\Actions;

use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\CenterIssueReports\DTOs\CenterIssueReportDTO;
use App\Domains\CenterIssueReports\Models\CenterIssueReport;
use App\Domains\Authentication\Models\User;
use Illuminate\Http\UploadedFile;
use Exception;

class UpdateCenterIssueReportAction
{
    public function __construct(
        private CenterIssueReportRepositoryInterface $repository
    ) {}

    public function execute(string $id, CenterIssueReportDTO $dto, User $user, ?int $enforcedCenterId = null, ?UploadedFile $attachment = null): CenterIssueReport
    {
        $report = $this->repository->getReportById($id, $enforcedCenterId);

        if (!$report) {
            throw new Exception("Report not found or unauthorized.", 404);
        }

        if ($user->isEvacPersonnel()) {
            if ($report->reported_by !== $user->user_id) {
                throw new Exception("You can only update your own report.", 403);
            }

            if ($report->status && $report->status->status_key !== 'open') {
                throw new Exception("Only open reports can be edited.", 400);
            }
        }

        $data = $dto->toArray();
        if ($attachment) {
            $data['attachment_path'] = $attachment->store('center_issues', 'public');
        }

        return $this->repository->updateReport($report, $data);
    }
}
