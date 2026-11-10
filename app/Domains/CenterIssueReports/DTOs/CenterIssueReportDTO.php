<?php

namespace App\Domains\CenterIssueReports\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class CenterIssueReportDTO
{
    public function __construct(
        public readonly int|string|null $evacuationCenterId,
        public readonly ?string $category,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $severity,
        public readonly bool $hasDescription = false,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            evacuationCenterId: $request->input('evacuation_center_id'),
            category:           $request->input('category'),
            title:              $request->input('title'),
            description:        $request->input('description'),
            severity:           $request->input('severity'),
            hasDescription:     $request->has('description'),
        );
    }
    
    public function toArray(): array
    {
        $data = array_filter([
            'evacuation_center_id' => $this->evacuationCenterId,
            'category'             => $this->category,
            'title'                => $this->title,
            'description'          => $this->description,
            'severity'             => $this->severity,
        ], fn($value) => $value !== null);

        if ($this->hasDescription && $this->description === null) {
            $data['description'] = null;
        }

        return $data;
    }
}
