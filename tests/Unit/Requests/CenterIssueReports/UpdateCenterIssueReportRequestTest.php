<?php

namespace Tests\Unit\Requests\CenterIssueReports;

use App\Domains\CenterIssueReports\Requests\UpdateCenterIssueReportRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateCenterIssueReportRequestTest extends TestCase
{
    private UpdateCenterIssueReportRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateCenterIssueReportRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('category', $rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('severity', $rules);
        $this->assertArrayHasKey('attachment', $rules);
    }

    public function test_validation_passes_with_empty_payload(): void
    {
        $validator = Validator::make([], $this->request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_when_category_is_invalid(): void
    {
        $validator = Validator::make([
            'category' => 'invalid_cat',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category'));
    }

    public function test_validation_fails_when_severity_is_invalid(): void
    {
        $validator = Validator::make([
            'severity' => 'urgent',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('severity'));
    }

    public function test_validation_fails_when_title_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'title' => str_repeat('X', 151),
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
    }

    public function test_validation_passes_with_valid_partial_update(): void
    {
        $image = UploadedFile::fake()->image('damage.jpg');

        $validator = Validator::make([
            'title'      => 'Resolved leak issue',
            'severity'   => 'low',
            'attachment' => $image,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
