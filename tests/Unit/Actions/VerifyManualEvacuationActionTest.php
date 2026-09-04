<?php

namespace Tests\Unit\Actions;

use App\Domains\Evacuations\Actions\VerifyManualEvacuationAction;
use App\Domains\Evacuations\DTOs\AdmissionDTO;
use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Models\Household;
use App\Exceptions\HouseholdAlreadyEvacuatedException;
use App\Exceptions\MembersAlreadyEvacuatedException;
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
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99,
            memberIds: []
        );

        $memberMock = Mockery::mock();
        $relationMock = Mockery::mock();
        $relationMock->shouldReceive('whereHas')->andReturnSelf();
        $relationMock->shouldReceive('exists')->andReturn(true);
        $memberMock->shouldReceive('evacuatedMembers')->andReturn($relationMock);

        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([$memberMock]));

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);

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

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99,
            memberIds: ['M-1', 'M-2']
        );

        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([
            (object)['member_id' => 'M-1'],
            (object)['member_id' => 'M-2']
        ]));

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);

        $evacRepo->shouldReceive('getEvacuatedCenterIdsForMembers')
            ->with(['M-1', 'M-2'])
            ->andReturn([2]);

        $action = new VerifyManualEvacuationAction($evacRepo, $houseRepo);

        $this->expectException(MembersAlreadyEvacuatedException::class);

        $action->execute($dto);
    }

    public function test_it_creates_evacuation_record_successfully_with_registered_members()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99,
            method: 'manual',
            eventId: null,
            memberIds: ['M-1', 'M-2']
        );

        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([
            (object)['member_id' => 'M-1'],
            (object)['member_id' => 'M-2']
        ]));

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);
        $householdMock->shouldReceive('fresh')->with(['members', 'address'])->andReturn($householdMock);

        $evacRepo->shouldReceive('resolveEventId')->andReturn('10');
        $evacRepo->shouldReceive('getEvacuatedCenterIdsForMembers')->andReturn([]);

        $mockRecord = new \App\Domains\Evacuations\Models\EvacuationRecord();
        $mockRecord->evacuation_id = 123;

        $evacRepo->shouldReceive('createRecord')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['household_id'] === 'HH-123' 
                    && (string)$data['center_id'] === '1' 
                    && $data['evacuated_count'] === 2
                    && $data['method'] === 'manual';
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

    public function test_it_creates_evacuation_record_successfully_with_unregistered_count()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99,
            method: 'manual',
            memberCount: 4
        );

        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([]));

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);
        $householdMock->shouldReceive('fresh')->with(['members', 'address'])->andReturn($householdMock);

        $evacRepo->shouldReceive('resolveEventId')->andReturn('10');

        $mockRecord = new \App\Domains\Evacuations\Models\EvacuationRecord();
        $mockRecord->evacuation_id = 456;

        $evacRepo->shouldReceive('createRecord')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['household_id'] === 'HH-123' 
                    && (string)$data['center_id'] === '1' 
                    && $data['evacuated_count'] === 4
                    && $data['method'] === 'manual';
            }))
            ->andReturn($mockRecord);

        $evacRepo->shouldReceive('createEvacuatedMembersWithCount')
            ->once()
            ->with($mockRecord, 4);
            
        $evacRepo->shouldReceive('findById')
            ->once()
            ->with(456)
            ->andReturn($mockRecord);

        $action = new VerifyManualEvacuationAction($evacRepo, $houseRepo);

        $result = $action->execute($dto);

        $this->assertIsArray($result);
        $this->assertEquals(456, $result['evacuation']->evacuation_id);
    }
}
