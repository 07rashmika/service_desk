<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_see_a_branded_404_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSeeText('Page not found')
            ->assertSeeText('Go to ServiceDesk');
    }

    public function test_signed_in_users_see_the_404_page_inside_the_app(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSeeText('Page not found')
            ->assertSeeText('Go to dashboard');
    }
}
