<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\V1\ChangePasswordRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthTest extends TestCase
{
    public function test_login_request_validation_passes_with_valid_data(): void
    {
        $data = [
            'login'       => 'admin@callshift.com',
            'password'    => 'Password123*',
            'device_name' => 'Chrome Windows',
        ];

        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_login_request_validation_fails_when_fields_missing(): void
    {
        $data = [
            'login' => '',
        ];

        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('login', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_change_password_request_requires_matching_confirmation(): void
    {
        $data = [
            'current_password'      => 'OldPassword123*',
            'password'              => 'NewPassword456*',
            'password_confirmation' => 'DifferentPassword!',
        ];

        $request = new ChangePasswordRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_change_password_request_enforces_complexity_rules(): void
    {
        $data = [
            'current_password'      => 'OldPassword123*',
            'password'              => 'simple', // Too short, no numbers, no symbols
            'password_confirmation' => 'simple',
        ];

        $request = new ChangePasswordRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }
}
