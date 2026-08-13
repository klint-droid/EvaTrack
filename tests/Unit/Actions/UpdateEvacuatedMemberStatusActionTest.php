<?php

namespace Tests\Unit\Actions;

use App\Domains\Evacuations\Actions\UpdateEvacuatedMemberStatusAction;
use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Evacuations\Models\EvacuationRecord;
use App\Domains\Evacuations\Models\EvacuatedMember;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class UpdateEvacuatedMemberStatusActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_throws_exception_for_invalid_status()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $mockRecord = Mockery::mock(EvacuationRecord::class);

        $evacRepo->shouldReceive('findById')
            ->once()
            ->with(10, null)
            ->andReturn($mockRecord);

        $action = new UpdateEvacuatedMemberStatusAction($evacRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid member status.');

        $action->execute(10, '101', 'InvalidStatus', null);
    }

    public function test_it_allows_null_center_id_for_admin_status_update()
    {
        $evacRepo = Mockery::mock(EvacuationRepositoryInterface::class);

        DB::shouldReceive('connection')->with('mysql_v2')->andReturnSelf();
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $mockRecord = Mockery::mock(EvacuationRecord::class);
        $mockRecord->shouldReceive('update')->once();

        $evacRepo->shouldReceive('findById')
            ->once()
            ->with(10, null)
            ->andReturn($mockRecord);

        $action = new UpdateEvacuatedMemberStatusAction($evacRepo);

        $result = $action->execute(10, '101', 'Checked Out', null);

        $this->assertInstanceOf(EvacuatedMember::class, $result);
        $this->assertEquals(10, $result->evacuation_id);
        $this->assertEquals('101', $result->member_id);
    }
}
