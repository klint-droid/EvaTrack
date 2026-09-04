<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;

class GenerateHouseholdQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan households:generate-qr          → all households
     * php artisan households:generate-qr --limit=10 → first N households
     */
    protected $signature = 'households:generate-qr
                            {--limit= : Limit the number of QR codes generated}
                            {--out=storage/app/qr_codes/households : Output directory (relative to project root)}';

    protected $description = 'Generate QR code images for every household_id in the database and save them to a test folder';

    public function handle(): int
    {
        $outputDir = base_path($this->option('out'));

        // Ensure the output directory exists
        File::ensureDirectoryExists($outputDir);

        $this->info("Output directory: {$outputDir}");

        // --- Fetch household IDs ---
        $query = DB::connection('mysql_v2')
            ->table('households')
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->select('household_id', 'household_name');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $households = $query->get();

        if ($households->isEmpty()) {
            $this->warn('No households found in the database.');
            return self::FAILURE;
        }

        $this->info("Found {$households->count()} household(s). Generating QR codes...");
        $bar = $this->output->createProgressBar($households->count());
        $bar->start();

        $generated = 0;
        $errors    = [];

        foreach ($households as $hh) {
            try {
                $filename = $outputDir . DIRECTORY_SEPARATOR . $hh->household_id . '.svg';

                // Generate an SVG QR code that encodes the household_id string.
                // SVG needs no PHP image extensions (imagick / gd).
                // Size 300 units, quiet margin of 2 cells.
                QrCode::format('svg')
                    ->size(300)
                    ->margin(2)
                    ->errorCorrection('H')
                    ->generate($hh->household_id, $filename);

                $generated++;
            } catch (\Throwable $e) {
                $errors[] = "{$hh->household_id}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅  Generated {$generated} QR code(s) in: {$outputDir}");

        if ($errors) {
            $this->warn('The following households failed:');
            foreach ($errors as $err) {
                $this->error("  • {$err}");
            }
        }

        // Write a manifest CSV for easy reference
        $manifestPath = $outputDir . DIRECTORY_SEPARATOR . '_manifest.csv';
        $csv = "household_id,household_name,qr_file\n";
        foreach ($households as $hh) {
            $csv .= "\"$hh->household_id\",\"$hh->household_name\",\"{$hh->household_id}.svg\"\n";
        }
        File::put($manifestPath, $csv);
        $this->info("📄  Manifest CSV written to: {$manifestPath}");

        return self::SUCCESS;
    }
}
