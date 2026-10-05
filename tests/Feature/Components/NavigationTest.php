<?php

namespace Tests\Feature\Components;

use App\Enums\RoleName;
use App\Models\User;
use App\View\Components\Layouts\Navigation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    protected function visibleLabels(): array
    {
        return collect((new Navigation)->visibleSections())
            ->flatMap(fn (array $section): array => array_column($section['items'], 'label'))
            ->all();
    }

    protected function registerRoute(string $uri, string $name): void
    {
        Route::get($uri, fn () => '')->name($name);
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_items_are_hidden_until_their_route_exists(): void
    {
        $this->actingAs(User::factory()->withRole(RoleName::Admin)->create());

        $this->assertNotContains('Users', $this->visibleLabels());
        $this->assertContains('Dashboard', $this->visibleLabels());
        $this->assertContains('Profile', $this->visibleLabels());
    }

    public function test_items_are_only_shown_to_users_with_the_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->registerRoute('/tickets', 'tickets.index');
        $this->registerRoute('/support/tickets', 'support.tickets.index');

        $this->actingAs(User::factory()->withRole(RoleName::Employee)->create());
        $this->assertContains('My Tickets', $this->visibleLabels());
        $this->assertNotContains('Ticket Queue', $this->visibleLabels());

        $this->actingAs(User::factory()->withRole(RoleName::Support)->create());
        $this->assertContains('Ticket Queue', $this->visibleLabels());
    }
}
