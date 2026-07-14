<?php

namespace Tests\Unit\Models;

use App\Domains\Authentication\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_name_attribute_concatenates_first_and_last_name()
    {
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $user->name);
    }

    public function test_name_attribute_handles_missing_names()
    {
        $user = new User([
            'first_name' => 'John',
        ]);

        $this->assertEquals('John', $user->name);
    }

    public function test_role_checks_return_true_for_correct_role()
    {
        $superAdmin = new User(['role_id' => User::ROLE_SUPER_ADMIN_ID]);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isEvacAdmin());
        $this->assertFalse($superAdmin->isEvacPersonnel());

        $evacAdmin = new User(['role_id' => User::ROLE_EVAC_ADMIN_ID]);
        $this->assertFalse($evacAdmin->isSuperAdmin());
        $this->assertTrue($evacAdmin->isEvacAdmin());
        $this->assertFalse($evacAdmin->isEvacPersonnel());

        $evacPersonnel = new User(['role_id' => User::ROLE_EVAC_PERSONNEL_ID]);
        $this->assertFalse($evacPersonnel->isSuperAdmin());
        $this->assertFalse($evacPersonnel->isEvacAdmin());
        $this->assertTrue($evacPersonnel->isEvacPersonnel());
    }

    public function test_can_manage_users_allows_admins()
    {
        $superAdmin = new User(['role_id' => User::ROLE_SUPER_ADMIN_ID]);
        $this->assertTrue($superAdmin->canManageUsers());

        $evacAdmin = new User(['role_id' => User::ROLE_EVAC_ADMIN_ID]);
        $this->assertTrue($evacAdmin->canManageUsers());

        $evacPersonnel = new User(['role_id' => User::ROLE_EVAC_PERSONNEL_ID]);
        $this->assertFalse($evacPersonnel->canManageUsers());
    }

    public function test_has_center_access_allows_admins_anywhere_and_personnel_if_assigned()
    {
        // Admin gets access anywhere
        $superAdmin = new User(['role_id' => User::ROLE_SUPER_ADMIN_ID]);
        $this->assertTrue($superAdmin->hasCenterAccess(99));

        // Personnel gets access only if it matches assigned_center_id
        $evacPersonnel = new User([
            'role_id' => User::ROLE_EVAC_PERSONNEL_ID,
            'assigned_center_id' => 10
        ]);
        
        $this->assertTrue($evacPersonnel->hasCenterAccess(10));
        $this->assertFalse($evacPersonnel->hasCenterAccess(99));
    }

    public function test_is_assigned_checks_if_center_is_not_null()
    {
        $userWithCenter = new User(['assigned_center_id' => 5]);
        $this->assertTrue($userWithCenter->isAssigned());

        $userWithoutCenter = new User(['assigned_center_id' => null]);
        $this->assertFalse($userWithoutCenter->isAssigned());
    }
}
