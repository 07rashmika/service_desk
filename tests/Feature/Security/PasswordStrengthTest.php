<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class PasswordStrengthTest extends TestCase
{
    public function test_passwords_need_at_least_eight_characters(): void
    {
        Http::preventStrayRequests();

        $this->assertTrue(Validator::make(['password' => 'short'], ['password' => Password::default()])->fails());
        $this->assertTrue(Validator::make(['password' => 'long-enough'], ['password' => Password::default()])->passes());

        // The breach check needs the internet, so it only runs in production.
        $this->assertTrue(Validator::make(['password' => 'password'], ['password' => Password::default()])->passes());
    }

    public function test_production_rejects_passwords_found_in_data_breaches(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        // "password" is SHA-1 5BAA61E4C9B93F3F0682250B6CF8331B7EE68FD8; the breach API is asked for its first 5 characters.
        Http::fake([
            'api.pwnedpasswords.com/range/5BAA6' => Http::response("1E4C9B93F3F0682250B6CF8331B7EE68FD8:9659365\r\n"),
            'api.pwnedpasswords.com/*' => Http::response(''),
        ]);

        $this->assertTrue(Validator::make(['password' => 'password'], ['password' => Password::default()])->fails());
        $this->assertTrue(Validator::make(['password' => 'kettle-orbit-91-lantern'], ['password' => Password::default()])->passes());
    }
}
