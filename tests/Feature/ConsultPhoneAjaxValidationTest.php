<?php

namespace Tests\Feature;

use App\Rules\ConsultPhoneNumber;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ConsultPhoneAjaxValidationTest extends TestCase
{
    public function test_validator_exposes_phone_field_error_for_ajax_contract(): void
    {
        $validator = Validator::make(
            [
                'name' => 'Jane Tester',
                'email' => 'jane@example.com',
                'content' => 'I would like a showing.',
            ],
            [
                'name' => ['required', 'string'],
                'phone' => ['bail', 'required', 'string', new ConsultPhoneNumber()],
                'content' => ['required', 'string'],
            ],
            ['phone.required' => PhoneNumberNormalizer::REQUIRED_MESSAGE]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertSame('Phone number is required.', $validator->errors()->first('phone'));

        $invalid = Validator::make(
            [
                'name' => 'Jane Tester',
                'phone' => '123',
                'content' => 'I would like a showing.',
            ],
            [
                'phone' => ['bail', 'required', 'string', new ConsultPhoneNumber()],
            ]
        );

        $this->assertTrue($invalid->fails());
        $this->assertSame('Please enter a valid phone number.', $invalid->errors()->first('phone'));

        $payload = ['message' => $invalid->errors()->first('phone'), 'errors' => $invalid->errors()->toArray()];
        $this->assertSame(422, \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertArrayHasKey('phone', $payload['errors']);
    }
}
