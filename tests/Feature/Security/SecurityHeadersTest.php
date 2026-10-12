<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function assertHasSecurityHeaders(TestResponse $response): void
    {
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_pages_redirects_errors_and_api_responses_have_security_headers(): void
    {
        $this->assertHasSecurityHeaders($this->get(route('login'))->assertOk());
        $this->assertHasSecurityHeaders($this->get(route('dashboard'))->assertRedirect(route('login')));
        $this->assertHasSecurityHeaders($this->get('/no-such-page')->assertNotFound());
        $this->assertHasSecurityHeaders($this->getJson(route('api.v1.tickets.index'))->assertUnauthorized());

        $this->actingAs(User::factory()->create());
        $this->assertHasSecurityHeaders($this->get(route('profile.edit'))->assertOk());
    }

    public function test_browsers_are_told_to_stick_to_https_only_over_https(): void
    {
        $this->get(route('login'))->assertHeaderMissing('Strict-Transport-Security');

        $this->get(str_replace('http://', 'https://', route('login')))
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
