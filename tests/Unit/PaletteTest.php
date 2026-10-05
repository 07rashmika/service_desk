<?php

namespace Tests\Unit;

use App\Enums\Palette;
use PHPUnit\Framework\TestCase;

class PaletteTest extends TestCase
{
    public function test_known_names_resolve_to_their_palette(): void
    {
        $this->assertSame(Palette::Orange, Palette::fromName('orange'));
        $this->assertSame(Palette::Red, Palette::fromName(Palette::Red));
    }

    public function test_unknown_or_missing_names_fall_back_to_slate(): void
    {
        $this->assertSame(Palette::Slate, Palette::fromName('chartreuse'));
        $this->assertSame(Palette::Slate, Palette::fromName(null));
    }

    public function test_every_palette_has_badge_and_dot_classes(): void
    {
        foreach (Palette::cases() as $palette) {
            $this->assertNotEmpty($palette->badgeClasses());
            $this->assertStringStartsWith('bg-', $palette->dotClass());
        }
    }
}
