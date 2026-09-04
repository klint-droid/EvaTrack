<?php

namespace Tests\Unit\Actions;

use App\Domains\Evacuations\Actions\ScanQREvacuationAction;
use App\Domains\Evacuations\DTOs\AdmissionDTO;
use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Models\Household;
use App\Exceptions\HouseholdAlreadyEvacuatedException;
use App\Exceptions\MembersAlreadyEvacuatedException;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ScanQREvacuationActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_throws_exception_if_household_has_no_members()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99
        );

        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([]));

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);

        $action = new ScanQREvacuationAction($evacRepo, $houseRepo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This household has no registered members.');

        $action->execute($dto);
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
        $relationMock->shouldReceive('exists')->andReturn(true); // already evacuated
        $memberMock->shouldReceive('evacuatedMembers')->andReturn($relationMock);

        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([$memberMock]));

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);

        $action = new ScanQREvacuationAction($evacRepo, $houseRepo);

        $this->expectException(HouseholdAlreadyEvacuatedException::class);
        $this->expectExceptionMessage('All members of this household are already evacuated.');

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
            memberIds: ['M-1']
        );

        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([
            (object)['member_id' => 'M-1']
        ]));

        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);

        $evacRepo->shouldReceive('getEvacuatedCenterIdsForMembers')
            ->with(['M-1'])
            ->andReturn([2]);

        $action = new ScanQREvacuationAction($evacRepo, $houseRepo);

        $this->expectException(MembersAlreadyEvacuatedException::class);

        $action->execute($dto);
    }

    public function test_it_successfully_scans_and_admits_household()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);
        $houseRepo = Mockery::mock(HouseholdRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $dto = new AdmissionDTO(
            householdId: 'HH-123',
            centerId: 1,
            userId: 99,
            method: 'qr',
            memberIds: ['M-1', 'M-2']
        );

        $householdMock = Mockery::mock(Household::class);
        $householdMock->shouldReceive('getAttribute')->with('members')->andReturn(collect([
            (object)['member_id' => 'M-1'],
            (object)['member_id' => 'M-2']
        ]));
        $houseRepo->shouldReceive('findWithRelations')->with('HH-123')->andReturn($householdMock);

        $evacRepo->shouldReceive('getEvacuatedCenterIdsForMembers')->andReturn([]);
        $evacRepo->shouldReceive('resolveEventId')->andReturn('10');

        $mockRecord = new \App\Domains\Evacuations\Models\EvacuationRecord();
        $mockRecord->evacuation_id = 123;

        $evacRepo->shouldReceive('createRecord')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['household_id'] === 'HH-123' 
                    && (string)$data['center_id'] === '1' 
                    && $data['evacuated_count'] === 2
                    && $data['method'] === 'qr';
            }))
            ->andReturn($mockRecord);

        $evacRepo->shouldReceive('createEvacuatedMembers')
            ->once()
            ->with($mockRecord, ['M-1', 'M-2']);
            
        $evacRepo->shouldReceive('findById')
            ->once()
            ->with(123)
            ->andReturn($mockRecord);

        $householdMock->shouldReceive('fresh')->with(['members', 'address'])->andReturn($householdMock);

        $action = new ScanQREvacuationAction($evacRepo, $houseRepo);

        $result = $action->execute($dto);

        $this->assertIsArray($result);
        $this->assertEquals(123, $result['record']->evacuation_id);
    }
}
