<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class EmailPreferencesTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    public function test_everything_is_emailed_until_switched_off(): void
    {
        $this->setUpTicketReferenceData();
        $user = $this->employee();

        foreach (NotificationPreference::cases() as $preference) {
            $this->assertTrue($user->wantsEmail($preference));
        }
    }

    public function test_people_can_switch_emails_off_from_their_profile(): void
    {
        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $user = $this->employee();

        $this->actingAs($user)
            ->put(route('profile.notifications'), ['email' => ['ticket_updates' => '1', 'comments' => '0', 'assignments' => '0']])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'notifications-updated');

        $user->refresh();
        $this->assertTrue($user->wantsEmail(NotificationPreference::TicketUpdates));
        $this->assertFalse($user->wantsEmail(NotificationPreference::Comments));
        $this->assertFalse($user->wantsEmail(NotificationPreference::Assignments));

        $this->actingAs($user)->get(route('profile.edit'))->assertSee('Email notifications');
    }
}
