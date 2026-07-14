<?php

namespace Tests\Unit\Actions;

use App\Domains\Evacuations\Actions\CheckoutEvacuationAction;
use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Households\Models\HouseholdStatus;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CheckoutEvacuationActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_throws_exception_if_already_checked_out()
    {
        // 1. Arrange
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $mockRecord = new EvacuationRecord();
        $mockRecord->household_status_id = HouseholdStatus::CHECKED_OUT;

        $evacRepo->shouldReceive('findById')
            ->once()
            ->with(99, 1)
            ->andReturn($mockRecord);

        $action = new CheckoutEvacuationAction($evacRepo);

        // 2. Expect Exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Household is already checked out.');

        // 3. Act
        $action->execute(99, 1);
    }

    public function test_it_successfully_checks_out_household()
    {
        // 1. Arrange
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $mockRecord = new EvacuationRecord();
        $mockRecord->household_status_id = HouseholdStatus::EVACUATED;

        $evacRepo->shouldReceive('findById')
            ->once()
            ->with(99, 1)
            ->andReturn($mockRecord);

        $evacRepo->shouldReceive('updateRecord')
            ->once()
            ->with(99, Mockery::on(function ($data) {
                return $data['household_status_id'] === HouseholdStatus::CHECKED_OUT 
                    && isset($data['checkout_at']);
            }));

        $updatedRecord = new EvacuationRecord();
        $updatedRecord->household_status_id = HouseholdStatus::CHECKED_OUT;

        $evacRepo->shouldReceive('findById')
            ->once()
            ->with(99)
            ->andReturn($updatedRecord);

        $action = new CheckoutEvacuationAction($evacRepo);

        // 2. Act
        $result = $action->execute(99, 1);

        // 3. Assert
        $this->assertEquals(HouseholdStatus::CHECKED_OUT, $result->household_status_id);
    }
}
