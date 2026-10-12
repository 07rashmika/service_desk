<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountSetupLinkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string|int>
     */
    protected function setPasswordForm(User $user, string $token, bool $welcome = true): array
    {
        return [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
            ...($welcome ? ['welcome' => 1] : []),
        ];
    }

    public function test_the_welcome_page_asks_the_person_to_set_their_password(): void
    {
        $this->get(route('password.reset', ['token' => 'abc', 'email' => 'new@example.com', 'welcome' => 1]))
            ->assertOk()
            ->assertSee('Set your password')
            ->assertSee('name="welcome" value="1"', false);
    }

    public function test_the_welcome_link_still_works_two_days_later(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('welcome')->createToken($user);

        $this->travel(2)->days();

        $this->post(route('password.update'), $this->setPasswordForm($user, $token))->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
        $this->assertFalse(Password::broker('welcome')->tokenExists($user, $token));
    }

    public function test_the_welcome_link_expires_after_three_days(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('welcome')->createToken($user);

        $this->travel(3)->days();
        $this->travel(1)->minutes();

        $this->post(route('password.update'), $this->setPasswordForm($user, $token))->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_ordinary_reset_links_still_expire_after_an_hour(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->travel(61)->minutes();

        $this->post(route('password.update'), $this->setPasswordForm($user, $token, welcome: false))->assertSessionHasErrors('email');
    }

    public function test_a_reset_token_is_not_accepted_as_a_welcome_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), $this->setPasswordForm($user, $token))->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_the_welcome_email_says_how_long_the_link_lasts(): void
    {
        $user = User::factory()->create();

        $mail = (new AccountCreated('token-123'))->toMail($user);

        $this->assertStringContainsString('welcome=1', $mail->actionUrl);
        $this->assertContains('This link expires in 3 days. After that, use "Forgot password?" on the sign-in page.', $mail->outroLines);
    }
}
