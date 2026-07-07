<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCenterIssueReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evacuation_center_id' => 'nullable|exists:evacuation_centers,evacuation_center_id',
            'category'             => 'required|in:incident,facility_issue,health_issue,safety_issue,other',
            'title'                => 'required|string|max:150',
            'description'          => 'required|string',
            'severity'             => 'required|in:low,medium,high,critical',
            'attachment'           => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ];
    }
}
