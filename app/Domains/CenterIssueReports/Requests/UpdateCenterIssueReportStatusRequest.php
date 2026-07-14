<?php

namespace App\Domains\CenterIssueReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCenterIssueReportStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:open,in_progress,resolved,closed',
        ];
    }
}
