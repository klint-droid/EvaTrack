<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    $conn = DB::connection('mysql_v2');
    echo "Connected successfully to: " . $conn->getDatabaseName() . "\n";

    // 1. Get collation of users table
    $usersTable = $conn->select("SHOW TABLE STATUS LIKE 'users'");
    $collation = $usersTable[0]->Collation ?? 'utf8mb4_unicode_ci';
    echo "Users table collation: {$collation}\n";

    // 2. Create center_personnel_assignments table
    $sql = "
    CREATE TABLE IF NOT EXISTS `center_personnel_assignments` (
        `assignment_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` VARCHAR(255) NOT NULL,
        `evacuation_center_id` VARCHAR(255) NOT NULL,
        `assigned_by` VARCHAR(255) DEFAULT NULL,
        `event_id` VARCHAR(255) DEFAULT NULL,
        `role_at_center` VARCHAR(100) DEFAULT 'personnel',
        `status` ENUM('active', 'completed', 'transferred') NOT NULL DEFAULT 'active',
        `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `relieved_at` TIMESTAMP NULL DEFAULT NULL,
        `remarks` TEXT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_cpa_user_status` (`user_id`, `status`),
        KEY `idx_cpa_center_status` (`evacuation_center_id`, `status`),
        KEY `idx_cpa_assigned_at` (`assigned_at`),
        CONSTRAINT `fk_cpa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_cpa_center` FOREIGN KEY (`evacuation_center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_cpa_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
        CONSTRAINT `fk_cpa_event` FOREIGN KEY (`event_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$collation};
    ";

    echo "Creating table center_personnel_assignments...\n";
    $conn->statement($sql);
    echo "Table center_personnel_assignments created or already exists.\n";

    // 3. Backfill active assignments from users where assigned_center_id is not null
    $usersWithCenter = $conn->table('users')
        ->whereNotNull('assigned_center_id')
        ->where('assigned_center_id', '!=', '')
        ->select('user_id', 'assigned_center_id', 'created_at')
        ->get();

    echo "Found " . $usersWithCenter->count() . " users with assigned_center_id.\n";

    $backfilledCount = 0;
    foreach ($usersWithCenter as $u) {
        // Check if already in center_personnel_assignments
        $exists = $conn->table('center_personnel_assignments')
            ->where('user_id', $u->user_id)
            ->where('evacuation_center_id', $u->assigned_center_id)
            ->where('status', 'active')
            ->exists();

        if (!$exists) {
            // Verify the evacuation center actually exists to avoid foreign key failure
            $centerExists = $conn->table('evacuation_centers')
                ->where('evacuation_center_id', $u->assigned_center_id)
                ->exists();

            if ($centerExists) {
                $conn->table('center_personnel_assignments')->insert([
                    'user_id'              => $u->user_id,
                    'evacuation_center_id' => $u->assigned_center_id,
                    'assigned_by'          => null,
                    'event_id'             => null,
                    'role_at_center'       => 'personnel',
                    'status'               => 'active',
                    'assigned_at'          => $u->created_at ?? now(),
                    'created_at'           => $u->created_at ?? now(),
                    'updated_at'           => now(),
                ]);
                $backfilledCount++;
                echo "  -> Backfilled user {$u->user_id} to center {$u->assigned_center_id}\n";
            } else {
                echo "  [SKIP] Center {$u->assigned_center_id} for user {$u->user_id} does not exist in evacuation_centers.\n";
            }
        } else {
            echo "  [SKIP] User {$u->user_id} already has active assignment to {$u->assigned_center_id}.\n";
        }
    }

    echo "Backfilled {$backfilledCount} assignments.\n";

    // 4. Verify table rows
    $totalAssignments = $conn->table('center_personnel_assignments')->count();
    echo "Total records in center_personnel_assignments: {$totalAssignments}\n";
    echo "SUCCESS!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
