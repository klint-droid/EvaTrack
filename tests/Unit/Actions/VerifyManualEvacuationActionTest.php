<?php

namespace Tests\Unit\Actions;

use App\Domains\Evacuations\Actions\VerifyManualEvacuationAction;
use App\Domains\Evacuations\DTOs\AdmissionDTO;
use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Models\Household;
use App\Exceptions\HouseholdAlreadyEvacuatedException;
use App\Exceptions\MembersAlreadyEvacuatedException;
use App\Exceptions\NoCenterAssignedException;
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

    public function test_it_throws_exception_if_center_has_no_active_event()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $centerMock = Mockery::mock('overload:' . EvacuationCenter::class);
        $centerMock->shouldReceive('where')->with('evacuation_center_id', 1)->andReturnSelf();

        $centerInstance = new EvacuationCenter();
        $centerInstance->current_event_id = null;
        $centerMock->shouldReceive('firstOrFail')->andReturn($centerInstance);

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99
        );

        $action = new VerifyManualEvacuationAction($evacRepo, $houseRepo);

        $this->expectException(NoCenterAssignedException::class);
        $this->expectExceptionMessage('Cannot admit household: This evacuation center is not assigned to an active disaster event.');

        $action->execute($dto);
    }

    public function test_it_throws_exception_if_household_already_evacuated()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $centerMock = Mockery::mock('overload:' . EvacuationCenter::class);
        $centerMock->shouldReceive('where')->with('evacuation_center_id', 1)->andReturnSelf();

        $centerInstance = new EvacuationCenter();
        $centerInstance->current_event_id = '10';
        $centerMock->shouldReceive('firstOrFail')->andReturn($centerInstance);

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

        $this->expectException(HouseholdAlreadyEvacuatedException::class);

        $action->execute($dto);
    }

    public function test_it_throws_exception_if_members_evacuated_elsewhere()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $centerMock = Mockery::mock('overload:' . EvacuationCenter::class);
        $centerMock->shouldReceive('where')->with('evacuation_center_id', 1)->andReturnSelf();

        $centerInstance = new EvacuationCenter();
        $centerInstance->current_event_id = '10';
        $centerMock->shouldReceive('firstOrFail')->andReturn($centerInstance);

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99,
            memberIds: ['M-1', 'M-2']
        );

        $evacRepo->shouldReceive('isHouseholdEvacuatedAtCenter')->andReturn(false);
        
        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([]));
        $householdMock->shouldReceive('getAttribute')->with('member_count')->andReturn(0);

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);

        $evacRepo->shouldReceive('resolveEventId')->andReturn(10);
        $evacRepo->shouldReceive('getEvacuatedCenterIdsForMembers')
            ->with(['M-1', 'M-2'])
            ->andReturn([2]);

        $action = new VerifyManualEvacuationAction($evacRepo, $houseRepo);

        $this->expectException(MembersAlreadyEvacuatedException::class);

        $action->execute($dto);
    }

    public function test_it_creates_evacuation_record_successfully()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $centerMock = Mockery::mock('overload:' . EvacuationCenter::class);
        $centerMock->shouldReceive('where')->with('evacuation_center_id', 1)->andReturnSelf();

        $centerInstance = new EvacuationCenter();
        $centerInstance->current_event_id = '10';
        $centerMock->shouldReceive('firstOrFail')->andReturn($centerInstance);

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
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([]));
        $householdMock->shouldReceive('getAttribute')->with('member_count')->andReturn(0);
        $householdMock->shouldReceive('update')->andReturn(true);

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);
        $householdMock->shouldReceive('fresh')->with(['members', 'address'])->andReturn($householdMock);

        $evacRepo->shouldReceive('resolveEventId')->andReturn(10);
        $evacRepo->shouldReceive('getEvacuatedCenterIdsForMembers')->andReturn([]);

        $mockRecord = new \App\Domains\Evacuations\Models\EvacuationRecord();
        $mockRecord->evacuation_id = 123;

        $evacRepo->shouldReceive('createRecord')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['household_id'] === 'HH-123' 
                    && (string)$data['center_id'] === '1' 
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

        $result = $action->execute($dto);

        $this->assertIsArray($result);
        $this->assertEquals(123, $result['evacuation']->evacuation_id);
    }
}
