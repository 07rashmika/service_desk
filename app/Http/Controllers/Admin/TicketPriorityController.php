<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordActivity;
use App\Enums\Palette;
use App\Http\Controllers\Controller;
use App\Models\TicketPriority;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketPriorityController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.priorities.index', [
            'priorities' => TicketPriority::query()->withCount('tickets')->ordered()->get(),
            'palettes' => Palette::cases(),
            'editing' => $request->integer('edit') ? TicketPriority::query()->find($request->integer('edit')) : null,
        ]);
    }

    public function store(Request $request, RecordActivity $recordActivity): RedirectResponse
    {
        $validated = $this->validated($request, 'createPriority');

        $priority = TicketPriority::query()->create([...$validated, 'slug' => $this->uniqueSlug($validated['name'])]);
        $recordActivity->handle($request->user(), 'settings.priority_created', "{$request->user()->name} added the {$priority->name} priority", $priority, new: $this->snapshot($priority));

        return redirect()->route('admin.priorities.index')->with('success', "Priority “{$priority->name}” added.");
    }

    /**
     * Changed SLA hours apply to tickets created from now on; existing deadlines stay as they were.
     */
    public function update(Request $request, TicketPriority $priority, RecordActivity $recordActivity): RedirectResponse
    {
        $before = $this->snapshot($priority);
        $priority->update($this->validated($request, 'editPriority'));
        $after = $this->snapshot($priority);
        $changed = array_keys(array_diff_assoc($after, $before));

        if ($changed !== []) {
            $recordActivity->handle(
                $request->user(),
                'settings.priority_updated',
                "{$request->user()->name} edited the {$priority->name} priority",
                $priority,
                array_intersect_key($before, array_flip($changed)),
                array_intersect_key($after, array_flip($changed)),
            );
        }

        return redirect()->route('admin.priorities.index')->with('success', "Priority “{$priority->name}” saved. New SLA targets apply to new tickets.");
    }

    public function destroy(Request $request, TicketPriority $priority, RecordActivity $recordActivity): RedirectResponse
    {
        if ($priority->tickets()->withTrashed()->exists()) {
            return back()->with('error', "“{$priority->name}” is used by tickets, so it can't be deleted.");
        }

        $priority->delete();
        $recordActivity->handle($request->user(), 'settings.priority_deleted', "{$request->user()->name} deleted the {$priority->name} priority");

        return back()->with('success', "Priority “{$priority->name}” deleted.");
    }

    /**
     * @return array{name: string, description: ?string, color: string, level: int, response_hours: int, resolution_hours: int}
     */
    protected function validated(Request $request, string $errorBag): array
    {
        return $request->validateWithBag($errorBag, [
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['required', Rule::enum(Palette::class)],
            'level' => ['required', 'integer', 'min:1', 'max:10'],
            'response_hours' => ['required', 'integer', 'min:1', 'max:720', 'lte:resolution_hours'],
            'resolution_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ], [
            'response_hours.lte' => 'The first response target must not be longer than the resolution target.',
        ]);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'priority';
        $slug = $base;

        for ($suffix = 2; TicketPriority::query()->where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function snapshot(TicketPriority $priority): array
    {
        return [
            'name' => $priority->name,
            'colour' => $priority->color->label(),
            'level' => (string) $priority->level,
            'response_hours' => (string) $priority->response_hours,
            'resolution_hours' => (string) $priority->resolution_hours,
        ];
    }
}
