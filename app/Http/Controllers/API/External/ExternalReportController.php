<?php

namespace App\Http\Controllers\API\External;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExternalReportController extends Controller
{
    /**
     * POST /api/v1/external/reports/generate
     * Generates report, stores it, and returns an expiring download link.
     */
    public function generate(Request $request): JsonResponse
    {
        // 1. Validate the payload
        $validated = $request->validate([
            'report_type' => 'required|string|in:demographics,dromic,utilization,vulnerable,general',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'format'      => 'nullable|string|in:pdf,csv',
        ]);

        $format = $validated['format'] ?? 'pdf';
        $reportType = $validated['report_type'];
        $timestamp = now()->format('Ymd_His');
        $randomHash = Str::random(8);
        $fileName = "{$reportType}_{$timestamp}_{$randomHash}.{$format}";
        $storagePath = "reports/{$fileName}";

        // 2. Generate file binary content
        if ($format === 'pdf') {
            $viewData = [
                'title'        => ucfirst($reportType) . ' Report',
                'meta'         => [
                    'event'        => 'External Request',
                    'center'       => 'All Centers',
                    'start_date'   => $validated['start_date'] ?? 'N/A',
                    'end_date'     => $validated['end_date'] ?? 'N/A',
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'generated_by' => 'External System API',
                ],
                // Pass existing EvaTrack sample or queried data
                'totalHouseholds'  => 0,
                'totalIndividuals' => 0,
                'male'             => 0,
                'female'           => 0,
                'children'         => 0,
                'youth'            => 0,
                'adults'           => 0,
                'elderly'          => 0,
                'vulnCounts'       => [],
            ];

            // Render existing Blade view from resources/views/exports/
            $pdf = Pdf::loadView('exports.demographic', $viewData)
                      ->setPaper('a4', 'landscape');
            $fileContent = $pdf->output();
        } else {
            // Render CSV content
            $fileContent = $this->generateCsvContent($reportType, $validated);
        }

        // 3. Save to active storage disk (currently 'local', later 's3')
        $disk = env('REPORT_DISK', 'local');
        Storage::disk($disk)->put($storagePath, $fileContent);

        // 4. Generate Expiring Download URL (Valid for 30 minutes)
        $expiresAt = now()->addMinutes(30);

        if ($disk === 's3') {
            // Pre-signed URL directly to AWS S3 bucket
            $downloadUrl = Storage::disk('s3')->temporaryUrl($storagePath, $expiresAt);
        } else {
            // Laravel native cryptographic signed route for local storage
            $downloadUrl = URL::temporarySignedRoute(
                'external.reports.download',
                $expiresAt,
                ['filename' => $fileName]
            );
        }

        // 5. Send JSON response back to the calling system
        return response()->json([
            'status'             => 'success',
            'message'            => 'Report generated successfully.',
            'report_type'        => $reportType,
            'format'             => $format,
            'file_name'          => $fileName,
            'download_url'       => $downloadUrl,
            'expires_at'         => $expiresAt->toIso8601String(),
            'expires_in_minutes' => 30,
        ], 200);
    }

    /**
     * GET /api/v1/external/reports/download/{filename}
     * Downloads the file from local storage using a signed link.
     */
    public function download(Request $request, string $filename): StreamedResponse
    {
        $disk = env('REPORT_DISK', 'local');
        $filePath = "reports/{$filename}";

        if (!Storage::disk($disk)->exists($filePath)) {
            abort(404, 'The requested report file does not exist or has expired.');
        }

        return Storage::disk($disk)->download($filePath);
    }

    /**
     * CSV generator helper
     */
    private function generateCsvContent(string $reportType, array $filters): string
    {
        $handle = fopen('php://temp', 'r+');
        // UTF-8 BOM for Excel
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, ['Report Type', ucfirst($reportType)]);
        fputcsv($handle, ['Date Range', ($filters['start_date'] ?? 'All') . ' to ' . ($filters['end_date'] ?? 'All')]);
        fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
        fputcsv($handle, []);
        fputcsv($handle, ['Record ID', 'Description', 'Timestamp']);
        fputcsv($handle, [1, 'Sample Record', now()->toDateTimeString()]);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }
}
