<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PageCachingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function assertNotStoredByBrowser(TestResponse $response): void
    {
        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    public function test_login_page_is_not_kept_by_the_browser(): void
    {
        $this->assertNotStoredByBrowser($this->get(route('login'))->assertOk());
    }

    public function test_signed_in_pages_are_not_kept_by_the_browser(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertNotStoredByBrowser($this->get(route('dashboard'))->assertOk());
        $this->assertNotStoredByBrowser($this->get(route('profile.edit'))->assertOk());
    }

    public function test_redirects_and_error_pages_are_not_kept_by_the_browser(): void
    {
        $this->assertNotStoredByBrowser($this->get(route('dashboard'))->assertRedirect(route('login')));
        $this->assertNotStoredByBrowser($this->get('/missing-page')->assertNotFound());
    }

    public function test_reopening_the_login_page_while_signed_in_goes_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('login'))->assertRedirect(route('dashboard'));
    }
}
