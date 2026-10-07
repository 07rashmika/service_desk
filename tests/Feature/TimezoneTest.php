<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_runs_on_sri_lanka_time(): void
    {
        $this->assertSame('Asia/Colombo', config('app.timezone'));
    }

    public function test_dates_are_saved_and_shown_in_local_time(): void
    {
        // 07:00 in London (UTC) is 12:30 in Colombo.
        $this->travelTo(Carbon::parse('2026-10-06 07:00:00', 'UTC'));

        User::factory()->create();

        $this->assertSame('2026-10-06 12:30:00', User::query()->toBase()->value('created_at'));
        $this->assertSame('12:30', User::sole()->created_at->format('H:i'));
    }

    public function test_the_dashboard_greeting_follows_local_time(): void
    {
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-06 07:00:00', 'UTC'));

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertSee('Good afternoon')
            ->assertDontSee('Good morning');
    }
}
