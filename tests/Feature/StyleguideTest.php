<?php

namespace Tests\Feature;

use Tests\TestCase;

class StyleguideTest extends TestCase
{
    public function test_styleguide_renders_every_component_group(): void
    {
        $this->withoutVite();

        $response = $this->get(route('styleguide'));

        $response->assertOk()
            ->assertSeeText('Style guide')
            ->assertSeeText('Waiting for User')
            ->assertSeeText('SD-000042')
            ->assertSeeText('Please provide a more descriptive summary (at least 10 characters).')
            ->assertSee('aria-label="Pagination"', false)
            ->assertSee('rel="next"', false)
            ->assertSeeText('Overdue 2h 5m');
    }
}
