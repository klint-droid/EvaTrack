<?php

namespace Tests\Unit\Requests\CenterIssueReports;

use App\Domains\CenterIssueReports\Requests\StoreCenterIssueReportRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class StoreCenterIssueReportRequestTest extends TestCase
{
    private StoreCenterIssueReportRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreCenterIssueReportRequest();
    }

    public function test_it_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('evacuation_center_id', $rules);
        $this->assertArrayHasKey('category', $rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('severity', $rules);
        $this->assertArrayHasKey('attachment', $rules);
    }

    public function test_prepare_for_validation_defaults_severity_to_medium(): void
    {
        $customRequest = StoreCenterIssueReportRequest::create('/test', 'POST', [
            'category'    => 'incident',
            'title'       => 'Some Issue',
            'description' => 'Details',
        ]);

        $reflection = new ReflectionMethod($customRequest, 'prepareForValidation');
        $reflection->invoke($customRequest);

        $this->assertEquals('medium', $customRequest->input('severity'));
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category'));
        $this->assertTrue($validator->errors()->has('title'));
        $this->assertTrue($validator->errors()->has('description'));
        $this->assertTrue($validator->errors()->has('severity'));
    }

    public function test_validation_fails_when_category_is_invalid(): void
    {
        $validator = Validator::make([
            'category'    => 'unknown_category',
            'title'       => 'Water Leak',
            'description' => 'Leak in restroom',
            'severity'    => 'high',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category'));
    }

    public function test_validation_fails_when_severity_is_invalid(): void
    {
        $validator = Validator::make([
            'category'    => 'facility_issue',
            'title'       => 'Broken door',
            'description' => 'Main door damaged',
            'severity'    => 'extreme_danger',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('severity'));
    }

    public function test_validation_fails_when_title_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'category'    => 'safety_issue',
            'title'       => str_repeat('T', 151),
            'description' => 'Test',
            'severity'    => 'low',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
    }

    public function test_validation_fails_when_attachment_has_invalid_mime(): void
    {
        $invalidFile = UploadedFile::fake()->create('script.sh', 50, 'application/x-sh');

        $validator = Validator::make([
            'category'    => 'other',
            'title'       => 'General notice',
            'description' => 'Some info',
            'severity'    => 'low',
            'attachment'  => $invalidFile,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('attachment'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $centerId = DB::connection('mysql_v2')->table('evacuation_centers')->value('evacuation_center_id');
        $validFile = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $validator = Validator::make([
            'evacuation_center_id' => $centerId,
            'category'             => 'facility_issue',
            'title'                => 'Power Generator Malfunction',
            'description'          => 'The auxiliary generator stopped working after heavy rain.',
            'severity'             => 'critical',
            'attachment'           => $validFile,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
