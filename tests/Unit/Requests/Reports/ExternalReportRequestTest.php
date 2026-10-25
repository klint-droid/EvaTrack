<?php

namespace Tests\Unit\Requests\Reports;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExternalReportRequestTest extends TestCase
{
    private array $rules = [
        'report_type' => 'required|string|in:demographics,dromic,utilization,vulnerable,general',
        'start_date'  => 'nullable|date',
        'end_date'    => 'nullable|date|after_or_equal:start_date',
        'format'      => 'nullable|string|in:pdf,csv',
    ];

    public function test_validation_fails_when_report_type_is_missing(): void
    {
        $validator = Validator::make([], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('report_type'));
    }

    public function test_validation_fails_when_report_type_is_invalid(): void
    {
        $validator = Validator::make([
            'report_type' => 'unknown_report',
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('report_type'));
    }

    public function test_validation_fails_when_format_is_invalid(): void
    {
        $validator = Validator::make([
            'report_type' => 'dromic',
            'format'      => 'xlsx', // only pdf,csv allowed
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('format'));
    }

    public function test_validation_fails_when_end_date_is_before_start_date(): void
    {
        $validator = Validator::make([
            'report_type' => 'utilization',
            'start_date'  => '2026-06-15',
            'end_date'    => '2026-06-10', // before start_date
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('end_date'));
    }

    public function test_validation_passes_for_all_valid_report_types(): void
    {
        $types = ['demographics', 'dromic', 'utilization', 'vulnerable', 'general'];

        foreach ($types as $type) {
            $validator = Validator::make([
                'report_type' => $type,
                'start_date'  => '2026-01-01',
                'end_date'    => '2026-01-31',
                'format'      => 'pdf',
            ], $this->rules);

            $this->assertFalse($validator->fails(), "Failed asserting report_type '{$type}' is valid.");
        }
    }
}
