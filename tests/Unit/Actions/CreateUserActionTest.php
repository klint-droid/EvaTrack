<?php

namespace Tests\Unit\Actions;

use App\Domains\Authentication\Actions\CreateUserAction;
use App\Domains\Authentication\DTOs\CreateUserDTO;
use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Repositories\UserRepositoryInterface;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CreateUserActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_aborts_with_403_when_evac_admin_attempts_to_create_user()
    {
        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $action = new CreateUserAction($userRepo);

        $evacAdmin = new User(['role_id' => User::ROLE_EVAC_ADMIN_ID]);
        $dto = new CreateUserDTO(
            firstName: 'Jane',
            lastName: 'Doe',
            password: 'password123',
            roleKey: 'evac_personnel',
            contactNumber: '09123456789'
        );

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Only Super Admin can create accounts.');

        $action->execute($evacAdmin, $dto);
    }

    public function test_it_aborts_with_403_when_personnel_attempts_to_create_user()
    {
        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $action = new CreateUserAction($userRepo);

        $personnel = new User(['role_id' => User::ROLE_EVAC_PERSONNEL_ID]);
        $dto = new CreateUserDTO(
            firstName: 'Jane',
            lastName: 'Doe',
            password: 'password123',
            roleKey: 'evac_personnel',
            contactNumber: '09123456789'
        );

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Only Super Admin can create accounts.');

        $action->execute($personnel, $dto);
    }
}
