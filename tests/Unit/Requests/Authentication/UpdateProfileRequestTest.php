<?php

namespace Tests\Unit\Requests\Authentication;

use App\Domains\Authentication\Models\User;
use App\Domains\Authentication\Requests\UpdateProfileRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateProfileRequestTest extends TestCase
{
    private UpdateProfileRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateProfileRequest();
    }

    public function test_it_denies_unauthenticated_user(): void
    {
        Auth::logout();
        $this->assertFalse($this->request->authorize());
    }

    public function test_it_authorizes_authenticated_user(): void
    {
        $user = new User();
        $user->user_id = 'PROFILE-USER-001';
        $this->actingAs($user);

        $this->assertTrue($this->request->authorize());
    }

    public function test_it_has_expected_rules(): void
    {
        $user = new User();
        $user->user_id = 'PROFILE-USER-001';
        $this->actingAs($user);

        $rules = $this->request->rules();
        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('contact_number', $rules);
        $this->assertArrayHasKey('profile_photo', $rules);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $user = new User();
        $user->user_id = 'PROFILE-USER-001';
        $this->actingAs($user);

        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('first_name'));
        $this->assertTrue($validator->errors()->has('last_name'));
        $this->assertTrue($validator->errors()->has('contact_number'));
    }

    public function test_validation_fails_when_first_name_exceeds_max_length(): void
    {
        $user = new User();
        $user->user_id = 'PROFILE-USER-001';
        $this->actingAs($user);

        $validator = Validator::make([
            'first_name'     => str_repeat('F', 256),
            'last_name'      => 'Doe',
            'contact_number' => '09123456789',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('first_name'));
    }

    public function test_validation_fails_when_profile_photo_is_not_an_image(): void
    {
        $user = new User();
        $user->user_id = 'PROFILE-USER-001';
        $this->actingAs($user);

        $notAnImage = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $validator = Validator::make([
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'contact_number' => '09' . rand(100000000, 999999999),
            'profile_photo'  => $notAnImage,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('profile_photo'));
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $user = new User();
        $user->user_id = 'PROFILE-USER-001';
        $this->actingAs($user);

        $image = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $validator = Validator::make([
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'contact_number' => '09' . rand(100000000, 999999999),
            'profile_photo'  => $image,
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }
}
