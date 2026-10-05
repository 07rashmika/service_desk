<?php

namespace Tests\Feature\Components;

use App\View\Components\Ui\SlaTimer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SlaTimerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeTime();
    }

    public function test_timer_without_a_deadline_shows_a_dash(): void
    {
        $timer = new SlaTimer;

        $this->assertSame('none', $timer->state);
        $this->assertSame('—', $timer->label);
    }

    public function test_met_and_paused_take_precedence_over_the_deadline(): void
    {
        $this->assertSame('met', (new SlaTimer(due: now()->subHour(), met: true))->state);
        $this->assertSame('paused', (new SlaTimer(due: now()->subHour(), paused: true))->state);
    }

    public function test_deadline_within_an_hour_is_due_soon(): void
    {
        $timer = new SlaTimer(due: now()->addMinutes(18));

        $this->assertSame('due-soon', $timer->state);
        $this->assertSame('18m left', $timer->label);
    }

    public function test_deadline_more_than_an_hour_away_is_ok(): void
    {
        $timer = new SlaTimer(due: now()->addMinutes(75));

        $this->assertSame('ok', $timer->state);
        $this->assertSame('1h 15m left', $timer->label);
    }

    public function test_past_deadline_is_overdue(): void
    {
        $timer = new SlaTimer(due: now()->subMinutes(42));

        $this->assertSame('overdue', $timer->state);
        $this->assertSame('Overdue 42m', $timer->label);
    }

    public function test_component_renders_the_label(): void
    {
        $this->blade('<x-ui.sla-timer :due="$due" />', ['due' => now()->addMinutes(18)])
            ->assertSeeText('18m left');
    }

    #[DataProvider('durations')]
    public function test_durations_use_the_two_largest_units(int $minutes, string $expected): void
    {
        $this->assertSame($expected, SlaTimer::formatMinutes($minutes));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function durations(): array
    {
        return [
            'zero' => [0, '0m'],
            'minutes only' => [18, '18m'],
            'whole hours' => [120, '2h'],
            'hours and minutes' => [125, '2h 5m'],
            'whole days' => [2880, '2d'],
            'days and hours' => [1680, '1d 4h'],
        ];
    }
}
