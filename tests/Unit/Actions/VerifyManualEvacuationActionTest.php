<?php

namespace Tests\Unit\Actions;

use App\Domains\Evacuations\Actions\VerifyManualEvacuationAction;
use App\Domains\Evacuations\DTOs\AdmissionDTO;
use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Models\Household;
use App\Exceptions\HouseholdAlreadyEvacuatedException;
use App\Exceptions\MembersAlreadyEvacuatedException;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class VerifyManualEvacuationActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_throws_exception_if_household_already_evacuated()
    {
        // 1. Arrange
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        // Mock DB Transaction to just execute the closure
        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        // Mock EvacuationCenter
        $centerMock = Mockery::mock('overload:' . EvacuationCenter::class);
        $centerMock->shouldReceive('where')->with('evacuation_center_id', 1)->andReturnSelf();
        $centerMock->shouldReceive('firstOrFail')->andReturn(new EvacuationCenter());

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99
        );

        $evacRepo->shouldReceive('isHouseholdEvacuatedAtCenter')
            ->with('HH-123', 1)
            ->once()
            ->andReturn(true);

        $action = new VerifyManualEvacuationAction($evacRepo, $houseRepo);

        // 2. Expect Exception
        $this->expectException(HouseholdAlreadyEvacuatedException::class);

        // 3. Act
        $action->execute($dto);
    }

    public function test_it_throws_exception_if_members_evacuated_elsewhere()
    {
        // 1. Arrange
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $centerMock = Mockery::mock('overload:' . EvacuationCenter::class);
        $centerMock->shouldReceive('where')->with('evacuation_center_id', 1)->andReturnSelf();
        $centerMock->shouldReceive('firstOrFail')->andReturn(new EvacuationCenter());

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99,
            method: 'manual',
            eventId: null,
            memberIds: ['M-1', 'M-2']
        );

        $evacRepo->shouldReceive('isHouseholdEvacuatedAtCenter')->andReturn(false);
        
        $household = new Household();
        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($household);

        $evacRepo->shouldReceive('resolveEventId')->andReturn(10);
        
        // Return that M-1 is already evacuated in center 2
        $evacRepo->shouldReceive('getEvacuatedCenterIdsForMembers')
            ->with(['M-1', 'M-2'])
            ->once()
            ->andReturn([2]);

        $action = new VerifyManualEvacuationAction($evacRepo, $houseRepo);

        // 2. Expect Exception
        $this->expectException(MembersAlreadyEvacuatedException::class);

        // 3. Act
        $action->execute($dto);
    }

    public function test_it_creates_evacuation_record_successfully()
    {
        // 1. Arrange
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $centerMock = Mockery::mock('overload:' . EvacuationCenter::class);
        $centerMock->shouldReceive('where')->with('evacuation_center_id', 1)->andReturnSelf();
        $centerMock->shouldReceive('firstOrFail')->andReturn(new EvacuationCenter());

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99,
            method: 'manual',
            eventId: null,
            memberIds: ['M-1', 'M-2']
        );

        $evacRepo->shouldReceive('isHouseholdEvacuatedAtCenter')->andReturn(false);
        
        $householdMock = Mockery::mock(Household::class);
        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);
        $householdMock->shouldReceive('fresh')->with(['members', 'address'])->andReturn($householdMock);

        $evacRepo->shouldReceive('resolveEventId')->andReturn(10);
        $evacRepo->shouldReceive('getEvacuatedCenterIdsForMembers')->andReturn([]);

        $mockRecord = new \App\Domains\Evacuations\Models\EvacuationRecord();
        $mockRecord->evacuation_id = 123;

        // Expect record creation
        $evacRepo->shouldReceive('createRecord')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['household_id'] === 'HH-123' 
                    && $data['center_id'] === 1 
                    && $data['evacuated_count'] === 2;
            }))
            ->andReturn($mockRecord);

        $evacRepo->shouldReceive('createEvacuatedMembers')
            ->once()
            ->with($mockRecord, ['M-1', 'M-2']);
            
        $evacRepo->shouldReceive('findById')
            ->once()
            ->with(123)
            ->andReturn($mockRecord);

        $action = new VerifyManualEvacuationAction($evacRepo, $houseRepo);

        // 2. Act
        $result = $action->execute($dto);

        // 3. Assert
        $this->assertIsArray($result);
        $this->assertEquals(123, $result['evacuation']->evacuation_id);
    }
}
