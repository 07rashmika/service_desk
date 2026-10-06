<?php

namespace App\View\Components\Ui;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SlaTimer extends Component
{
    /**
     * Minutes before the deadline at which the timer is highlighted as due soon.
     */
    public const DUE_SOON_MINUTES = 60;

    /**
     * One of: none, met, missed, paused, ok, due-soon, overdue.
     */
    public string $state;

    public string $label;

    public function __construct(
        public ?CarbonInterface $due = null,
        public bool $met = false,
        public bool $paused = false,
        public bool $missed = false,
    ) {
        [$this->state, $this->label] = $this->resolveState();
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveState(): array
    {
        if ($this->met) {
            return ['met', 'Met'];
        }

        if ($this->missed) {
            return ['missed', 'Missed'];
        }

        if ($this->paused) {
            return ['paused', 'Paused'];
        }

        if ($this->due === null) {
            return ['none', '—'];
        }

        $secondsLeft = (int) now()->diffInSeconds($this->due, false);

        if ($secondsLeft < 0) {
            return ['overdue', 'Overdue '.self::formatMinutes(intdiv(abs($secondsLeft), 60))];
        }

        $minutesLeft = intdiv($secondsLeft, 60);
        $state = $minutesLeft < self::DUE_SOON_MINUTES ? 'due-soon' : 'ok';

        return [$state, self::formatMinutes($minutesLeft).' left'];
    }

    /**
     * Format a duration using its two largest units, e.g. "1d 4h", "2h 5m", "18m".
     */
    public static function formatMinutes(int $minutes): string
    {
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        if ($days > 0) {
            return $hours > 0 ? "{$days}d {$hours}h" : "{$days}d";
        }

        if ($hours > 0) {
            return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
        }

        return "{$mins}m";
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.sla-timer');
    }
}
