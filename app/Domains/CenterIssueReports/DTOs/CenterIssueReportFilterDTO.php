<?php

namespace App\Domains\CenterIssueReports\DTOs;

use Illuminate\Http\Request;

class CenterIssueReportFilterDTO
{
    public function __construct(
        public readonly ?string $centerId,
        public readonly ?string $category,
        public readonly ?string $severity,
        public readonly ?string $status,
        public readonly ?string $q,
        public readonly int $limit,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            centerId: $request->has('center_id') ? (string) $request->query('center_id') : null,
            category: $request->query('category'),
            severity: $request->query('severity'),
            status:   $request->query('status'),
            q:        $request->query('q'),
            limit:    (int) $request->query('limit', 0),
        );
    }
}
