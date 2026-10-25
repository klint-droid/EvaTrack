<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\Notifications\Models\UserNotification;
use App\Domains\Notifications\Services\TextBeeService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AssignCenterAction
 *
 * Adheres to Single Responsibility Principle (SRP).
 * Validates center assignment authorization, idempotently updates assigned_center_id,
 * and notifies personnel via in-app notification and SMS.
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
        // Evac admin cannot assign a super admin
        if ($authUser->isEvacAdmin() && $targetUser->role?->role_key === 'super_admin') {
            throw new HttpException(403, 'You cannot assign a super admin');
        }

        // Idempotent check: if already assigned to this center, no-op
        if ($targetUser->assigned_center_id === $assignedCenterId) {
            return [
                'user'             => $targetUser,
                'already_assigned' => true,
            ];
        }

        $oldCenterId = $targetUser->assigned_center_id;

        $updated = $this->userRepository->update($targetUser, [
            'assigned_center_id' => $assignedCenterId,
        ]);

        // Send notifications (In-app + SMS)
        $this->notifyPersonnel($authUser, $targetUser, $assignedCenterId, $oldCenterId);

        return [
            'user'             => $updated,
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
