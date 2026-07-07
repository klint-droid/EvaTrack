<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCenterIssueReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'    => 'sometimes|in:incident,facility_issue,health_issue,safety_issue,other',
            'title'       => 'sometimes|string|max:150',
            'description' => 'sometimes|string',
            'severity'    => 'sometimes|in:low,medium,high,critical',
            'attachment'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ];
    }
}
