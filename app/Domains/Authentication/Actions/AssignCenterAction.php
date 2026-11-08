<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\Models\CenterPersonnelAssignment;
use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Notifications\Models\UserNotification;
use App\Domains\Notifications\Services\TextBeeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AssignCenterAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Validates center assignment authorization, logs assignments in center_personnel_assignments,
 * maintains backwards-compatible users.assigned_center_id, and notifies personnel via in-app & SMS.
 */
class AssignCenterAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TextBeeService $textBeeService
    ) {}

    /**
     * @return array{user: User, already_assigned: bool}
     */
    public function execute(User $authUser, User $targetUser, ?string $assignedCenterId): array
    {
        $targetUser = $targetUser->fresh() ?? $targetUser;

        // Evac admin cannot assign a super admin
        if ($authUser->isEvacAdmin() && $targetUser->role?->role_key === 'super_admin') {
            throw new HttpException(403, 'You cannot assign a super admin');
        }

        // Idempotent check: if already assigned to this center and active assignment exists
        if ($targetUser->assigned_center_id === $assignedCenterId) {
            $hasActiveAssignment = CenterPersonnelAssignment::where('user_id', $targetUser->user_id)
                ->where('evacuation_center_id', $assignedCenterId)
                ->active()
                ->exists();

            if ($hasActiveAssignment || empty($assignedCenterId)) {
                return [
                    'user'             => $targetUser->loadMissing(['role', 'assignedCenter', 'currentPersonnelAssignment.center']),
                    'already_assigned' => true,
                ];
            }
        }

        $oldCenterId = $targetUser->assigned_center_id;

        $updated = DB::connection('mysql_v2')->transaction(function () use ($authUser, $targetUser, $assignedCenterId) {
            if (!empty($assignedCenterId)) {
                // Verify target center exists
                $center = EvacuationCenter::find($assignedCenterId);
                if (!$center) {
                    throw new HttpException(404, 'Evacuation center not found');
                }

                // 1. Relieve any current active assignment(s) as transferred
                CenterPersonnelAssignment::where('user_id', $targetUser->user_id)
                    ->active()
                    ->update([
                        'status'      => 'transferred',
                        'relieved_at' => now(),
                        'remarks'     => "Transferred to center {$assignedCenterId} ({$center->name}) by {$authUser->user_id}",
                    ]);

                // 2. Create new active assignment record
                CenterPersonnelAssignment::create([
                    'user_id'              => $targetUser->user_id,
                    'evacuation_center_id' => $assignedCenterId,
                    'assigned_by'          => $authUser->user_id,
                    'event_id'             => $center->current_event_id,
                    'role_at_center'       => 'personnel',
                    'status'               => 'active',
                    'assigned_at'          => now(),
                ]);

                // 3. Keep users.assigned_center_id in sync
                return $this->userRepository->update($targetUser, [
                    'assigned_center_id' => $assignedCenterId,
                ]);
            } else {
                // Unassignment / Relieving
                CenterPersonnelAssignment::where('user_id', $targetUser->user_id)
                    ->active()
                    ->update([
                        'status'      => 'completed',
                        'relieved_at' => now(),
                        'remarks'     => "Relieved from assignment by {$authUser->user_id}",
                    ]);

                return $this->userRepository->update($targetUser, [
                    'assigned_center_id' => null,
                ]);
            }
        });

        // Send notifications (In-app + SMS)
        $this->notifyPersonnel($authUser, $targetUser, $assignedCenterId, $oldCenterId);

        return [
            'user'             => $updated->load(['role', 'assignedCenter', 'currentPersonnelAssignment.center']),
            'already_assigned' => false,
        ];
    }

    /**
     * Notify personnel of center assignment/unassignment via in-app notification and SMS.
     */
    protected function notifyPersonnel(User $authUser, User $targetUser, ?string $newCenterId, ?string $oldCenterId): void
    {
        $adminName = trim($authUser->name) ?: 'EvaTrack Administration';

        if (!empty($newCenterId)) {
            $center = EvacuationCenter::find($newCenterId);
            $centerName = $center ? $center->name : 'Assigned Evacuation Center';

            // 1. In-App Notification
            try {
                UserNotification::create([
                    'user_id' => $targetUser->user_id,
                    'type'    => 'center_assignment',
                    'title'   => 'Evacuation Center Assignment',
                    'message' => "You have been assigned to {$centerName} by {$adminName}.",
                    'data'    => [
                        'center_id'   => $newCenterId,
                        'center_name' => $centerName,
                        'assigned_by' => $adminName,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('[AssignCenterAction] Failed to create in-app notification: ' . $e->getMessage());
            }

            // 2. SMS Notification via TextBee
            if (!empty($targetUser->contact_number)) {
                $recipientPhone = $this->normalizePhoneNumber($targetUser->contact_number);
                $smsMessage = "EvaTrack Alert: Hello {$targetUser->first_name}, you have been assigned to {$centerName} by {$adminName}. Please report to the center or check your EvaTrack portal.";

                try {
                    $this->textBeeService->send($recipientPhone, $smsMessage);
                } catch (\Throwable $e) {
                    Log::warning('[AssignCenterAction] Failed to send assignment SMS: ' . $e->getMessage(), [
                        'user_id' => $targetUser->user_id,
                        'phone'   => $recipientPhone,
                    ]);
                }
            }
        } else {
            // Unassignment
            $oldCenterName = null;
            if (!empty($oldCenterId)) {
                $oldCenter = EvacuationCenter::find($oldCenterId);
                $oldCenterName = $oldCenter ? $oldCenter->name : null;
            }

            $unassignMsg = $oldCenterName
                ? "You have been unassigned from {$oldCenterName} by {$adminName}."
                : "You have been unassigned from your evacuation center by {$adminName}.";

            // 1. In-App Notification
            try {
                UserNotification::create([
                    'user_id' => $targetUser->user_id,
                    'type'    => 'center_unassignment',
                    'title'   => 'Center Assignment Updated',
                    'message' => $unassignMsg,
                    'data'    => [
                        'assigned_by' => $adminName,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('[AssignCenterAction] Failed to create in-app unassignment notification: ' . $e->getMessage());
            }

            // 2. SMS Notification via TextBee
            if (!empty($targetUser->contact_number)) {
                $recipientPhone = $this->normalizePhoneNumber($targetUser->contact_number);
                $smsMessage = "EvaTrack Alert: Hello {$targetUser->first_name}, {$unassignMsg}";

                try {
                    $this->textBeeService->send($recipientPhone, $smsMessage);
                } catch (\Throwable $e) {
                    Log::warning('[AssignCenterAction] Failed to send unassignment SMS: ' . $e->getMessage(), [
                        'user_id' => $targetUser->user_id,
                        'phone'   => $recipientPhone,
                    ]);
                }
            }
        }
    }

    /**
     * Normalizes a Philippine contact number into standard E.164 (+63...).
     */
    protected function normalizePhoneNumber(string $contactNumber): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $contactNumber);

        if (str_starts_with($cleaned, '09')) {
            return '+63' . substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '9') && strlen($cleaned) === 10) {
            return '+63' . $cleaned;
        }

        if (str_starts_with($cleaned, '639')) {
            return '+' . $cleaned;
        }

        return str_starts_with($contactNumber, '+') ? $contactNumber : '+' . $cleaned;
    }
}
