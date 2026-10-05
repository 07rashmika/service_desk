<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_the_users_details(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['name' => 'Nimal Perera', 'phone' => '+94 77 123 4567']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Nimal Perera')
            ->assertSee('+94 77 123 4567');
    }

    public function test_users_can_update_their_name_and_phone_but_not_their_email(): void
    {
        $user = User::factory()->create(['email' => 'nimal@example.com']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('user-profile-information.update'), [
                'name' => 'Nimal P.',
                'phone' => '+94 77 765 4321',
                'email' => 'someone-else@example.com',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-information-updated');

        $user->refresh();
        $this->assertSame('Nimal P.', $user->name);
        $this->assertSame('+94 77 765 4321', $user->phone);
        $this->assertSame('nimal@example.com', $user->email);
    }

    public function test_phone_numbers_with_letters_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), ['name' => $user->name, 'phone' => 'call me maybe'])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'phone');
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_users_can_change_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }
}
