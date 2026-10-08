<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
    }

    public function test_a_token_is_issued_for_the_right_password_and_works_on_later_requests(): void
    {
        $user = $this->employee();

        $response = $this->postJson(route('api.v1.auth.token'), ['email' => $user->email, 'password' => 'password', 'device_name' => 'Postman'])
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', 'employee')
            ->assertJsonStructure(['token', 'expires_at', 'user' => ['id', 'name', 'email', 'permissions']]);

        $this->withToken($response->json('token'))
            ->getJson(route('api.v1.me'))
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_a_wrong_password_gets_no_token(): void
    {
        $user = $this->employee();

        $this->postJson(route('api.v1.auth.token'), ['email' => $user->email, 'password' => 'nope', 'device_name' => 'Postman'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_deactivated_accounts_get_no_token_and_lose_existing_ones(): void
    {
        $user = $this->employee();
        $token = $user->createToken('phone')->plainTextToken;
        $user->forceFill(['is_active' => false])->save();

        $this->postJson(route('api.v1.auth.token'), ['email' => $user->email, 'password' => 'password', 'device_name' => 'Postman'])
            ->assertForbidden();

        $this->withToken($token)->getJson(route('api.v1.me'))->assertForbidden();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_signing_out_revokes_the_token(): void
    {
        $user = $this->employee();
        $token = $user->createToken('phone')->plainTextToken;

        $this->withToken($token)->deleteJson(route('api.v1.auth.revoke'))->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_requests_without_a_token_are_refused(): void
    {
        $this->getJson(route('api.v1.tickets.index'))->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_token_requests_are_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('api.v1.auth.token'), ['email' => $user->email, 'password' => 'wrong', 'device_name' => 'x']);
        }

        $this->postJson(route('api.v1.auth.token'), ['email' => $user->email, 'password' => 'wrong', 'device_name' => 'x'])
            ->assertTooManyRequests();
    }
}
