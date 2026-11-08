<?php

namespace App\Domains\Authentication\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class UserResource
 *
 * Adheres to Liskov Substitution Principle (LSP) & Single Responsibility Principle (SRP).
 * Provides a standardized, predictable contract for User JSON serialization across all endpoints:
 * - POST /login
 * - GET  /user
 * - GET  /users
 * - POST /users
 * - PUT  /users/{id}
 * - POST /users/{id}/assign-center
 * - PUT  /user/profile
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Use getRawOriginal to read the actual DB value before any cast is applied.
        // Prevents integer casts from coercing null into 0.
        $rawCenterId = $this->getRawOriginal('assigned_center_id');

        $assignedCenterData = null;
        if ($this->assignedCenter) {
            \App\Domains\Households\Models\HouseholdStatus::ensureSeeded();

            $activeEvacQuery = \App\Domains\Evacuations\Models\EvacuationRecord::where('center_id', $this->assignedCenter->evacuation_center_id)
                ->where('household_status_id', \App\Domains\Households\Models\HouseholdStatus::EVACUATED)
                ->where(function ($q) {
                    $q->whereNull('event_id')
                      ->orWhereHas('event', function ($eq) {
                          $eq->whereNull('ended_at');
                      });
                });

            $currentOccupancy = (int) (clone $activeEvacQuery)->sum('evacuated_count');
            $householdCount   = (int) (clone $activeEvacQuery)->distinct('household_id')->count('household_id');

            $assignedCenterData = [
                'id'                   => (string) $this->assignedCenter->evacuation_center_id,
                'evacuation_center_id' => (string) $this->assignedCenter->evacuation_center_id,
                'name'                 => $this->assignedCenter->name,
                'osm_address'          => $this->assignedCenter->osm_address,
                'capacity'             => (int) ($this->assignedCenter->capacity ?? 0),
                'current_occupancy'    => $currentOccupancy,
                'household_count'      => $householdCount,
            ];
        }

        return [
            'user_id'            => $this->user_id,
            'first_name'         => $this->first_name,
            'last_name'          => $this->last_name,
            'name'               => $this->name,
            'email'              => $this->email,
            'role_id'            => (int) $this->role_id,
            'role'               => $this->role?->role_key,
            'role_label'         => $this->role?->role_name ?? match ($this->role?->role_key) {
                'super_admin'    => 'Super Admin',
                'evac_admin'     => 'Evacuation Admin',
                'evac_personnel' => 'Evacuation Personnel',
                default          => 'User',
            },
            'assigned_center_id' => $rawCenterId !== null ? (string) $rawCenterId : null,
            'assigned_center'    => $assignedCenterData,
            'contact_number'     => $this->contact_number,
            'profile_photo_url'  => $this->profile_photo_url,
            'is_active'          => (bool) ($this->is_active ?? true),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
