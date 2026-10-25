<?php

namespace Tests\Unit\Requests\ResourceRequests;

use App\Domains\ResourceRequests\Requests\UpdateResourceRequestStatusRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateResourceRequestStatusRequestTest extends TestCase
{
    private UpdateResourceRequestStatusRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateResourceRequestStatusRequest();
    }

    public function test_it_denies_evac_admin(): void
    {
        $evacAdmin = new \App\Domains\Authentication\Models\User(['role_id' => \App\Domains\Authentication\Models\User::ROLE_EVAC_ADMIN_ID]);
        $this->request->setUserResolver(fn () => $evacAdmin);
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_denies_super_admin(): void
    {
        $superAdmin = new \App\Domains\Authentication\Models\User(['role_id' => \App\Domains\Authentication\Models\User::ROLE_SUPER_ADMIN_ID]);
        $this->request->setUserResolver(fn () => $superAdmin);
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_authorizes_when_not_admin(): void
    {
        $this->request->setUserResolver(fn () => null);
        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $rules = $this->request->rules();
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_validation_fails_when_status_is_missing(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_fails_when_status_is_invalid(): void
    {
        $validator = Validator::make([
            'status' => 'cancelled_by_user',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_passes_for_all_valid_statuses(): void
    {
        $validStatuses = ['pending', 'acknowledged', 'approved', 'rejected', 'delivered'];

        foreach ($validStatuses as $status) {
            $validator = Validator::make([
                'status' => $status,
            ], $this->request->rules());

            $this->assertFalse($validator->fails(), "Failed asserting status '{$status}' is valid.");
        }
    }
}
