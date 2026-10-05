<?php

namespace Tests\Feature\Auth;

use App\Http\Responses\PasswordResetLinkRequestedResponse;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSeeText('Forgot your password?');
    }

    public function test_reset_link_is_emailed(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', PasswordResetLinkRequestedResponse::MESSAGE);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_emails_get_the_same_message_so_accounts_cannot_be_discovered(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', PasswordResetLinkRequestedResponse::MESSAGE);

        Notification::assertNothingSent();
    }

    public function test_repeat_requests_within_the_throttle_window_get_the_same_message(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', PasswordResetLinkRequestedResponse::MESSAGE);

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_password_can_be_reset_with_the_emailed_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->get(route('password.reset', $notification->token))
                ->assertOk()
                ->assertSeeText('Choose a new password');

            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }
}
