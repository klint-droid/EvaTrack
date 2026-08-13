<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Base controller for all API controllers.
 *
 * Provides shared authorization and center-resolution helpers
 * to eliminate duplicated boilerplate across controllers.
 */
class BaseApiController extends Controller
{
    /**
     * Abort with 403 if the authenticated user does not hold one of the given roles.
     *
     * Usage: $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
     */
    protected function authorizeRole(string ...$roles): void
    {
        $user = Auth::user();

        $roleChecks = [
            'super_admin'    => fn () => $user->isSuperAdmin(),
            'evac_admin'     => fn () => $user->isEvacAdmin(),
            'evac_personnel' => fn () => $user->isEvacPersonnel(),
        ];

        foreach ($roles as $role) {
            if (isset($roleChecks[$role]) && $roleChecks[$role]()) {
                return;
            }
        }

        abort(403, 'Unauthorized');
    }

    /**
     * Resolve the effective center ID for the current request.
     *
     * - Super admins / evac admins may specify `center_id` in the request.
     * - Evac personnel are locked to their assigned center.
     * - Aborts with 403 if no center can be determined.
     */
    protected function resolveUserCenterId(Request $request, bool $required = true): ?string
    {
        $user = Auth::user();

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                abort(403, 'No center assigned.');
            }
            return (string) $user->assigned_center_id;
        }

        $centerId = $request->input('center_id') ?? $request->query('center_id') ?? $user->assigned_center_id;

        if (!$centerId && $required) {
            abort(400, 'center_id is required for admins.');
        }

        return $centerId ? (string) $centerId : null;
    }

    /**
     * Assert that an evac-personnel user has a center assigned.
     * Aborts with 403 if the user is personnel without an assignment.
     */
    protected function ensurePersonnelHasCenter(): void
    {
        $user = Auth::user();

        if ($user->isEvacPersonnel() && !$user->assigned_center_id) {
            abort(403, 'No evacuation center assigned');
        }
    }

    /**
     * Restricts a query builder to the user's assigned center (if personnel).
     * If admin/super_admin, applies center_id filter if present in request.
     */
    protected function applyCenterFilter($query, Request $request = null)
    {
        $user = Auth::user();

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                abort(403, 'No evacuation center assigned');
            }
            if ($request && $request->filled('center_id') && $request->center_id != $user->assigned_center_id) {
                abort(403, 'You are not assigned to this evacuation center');
            }
            $query->where('center_id', $user->assigned_center_id);
        } else if ($request && $request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        return $query;
    }

    /**
     * Checks if the user is allowed to mutate records in the given center_id.
     */
    protected function checkCenterOwnership($centerId): void
    {
        $user = Auth::user();

        if ($user->isEvacPersonnel() && $user->assigned_center_id != $centerId) {
            abort(403, 'Unauthorized');
        }
    }
}
