<?php

namespace Tests\Feature\Components;

use App\View\Components\Layouts\Navigation;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    public function test_items_are_hidden_until_their_route_exists(): void
    {
        $this->assertSame([], (new Navigation)->visibleSections());
    }

    public function test_items_with_a_registered_route_are_shown(): void
    {
        Route::get('/dashboard', fn () => '')->name('dashboard');
        Route::getRoutes()->refreshNameLookups();

        $sections = (new Navigation)->visibleSections();

        $this->assertCount(1, $sections);
        $this->assertSame('Dashboard', $sections[0]['items'][0]['label']);
        $this->assertSame(url('/dashboard'), $sections[0]['items'][0]['href']);
    }
}
