<?php

namespace App\Domains\Evacuations\Actions;

use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Households\Models\HouseholdStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckoutEvacuationAction
{
    public function __construct(
        private EvacuationRepositoryInterface $evacuationRepository
    ) {}

    public function execute(int $evacuationId, int $centerId): EvacuationRecord
    {
        return DB::connection('mysql_v2')->transaction(function () use ($evacuationId, $centerId) {
            $record = $this->evacuationRepository->findById($evacuationId, $centerId);

            if ($record->household_status_id === HouseholdStatus::CHECKED_OUT) {
                throw new \Exception('Household is already checked out.');
            }

            $this->evacuationRepository->updateRecord($evacuationId, [
                'household_status_id' => HouseholdStatus::CHECKED_OUT,
                'checkout_at'         => Carbon::now()
            ]);

            // Note: Originally EvacuationService also updated unit allocation here.
            // If they are checked out, unit allocations were resolved in EvacuationController or Service.
            // Let's verify what `EvacuationService@handleCheckout` did.
            // In the original service, it updated status and checkout_at. That's it.
            // (Unit allocations might be handled via a listener or in the controller directly).

            return $this->evacuationRepository->findById($evacuationId);
        });
    }
}
