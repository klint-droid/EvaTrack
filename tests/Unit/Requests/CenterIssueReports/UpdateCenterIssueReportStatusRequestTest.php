<?php

namespace Tests\Unit\Requests\CenterIssueReports;

use App\Domains\CenterIssueReports\Requests\UpdateCenterIssueReportStatusRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateCenterIssueReportStatusRequestTest extends TestCase
{
    private UpdateCenterIssueReportStatusRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateCenterIssueReportStatusRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_validation_fails_when_status_is_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_fails_when_status_is_invalid(): void
    {
        $validator = Validator::make([
            'status' => 'pending_review',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_passes_for_all_valid_statuses(): void
    {
        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];

        foreach ($validStatuses as $status) {
            $validator = Validator::make([
                'status' => $status,
            ], $this->request->rules());

            $this->assertFalse($validator->fails(), "Failed asserting status '{$status}' is valid.");
        }
    }
}
