<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordActivity;
use App\Enums\Palette;
use App\Http\Controllers\Controller;
use App\Models\TicketStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The six workflow statuses are fixed; admins can only rename and recolour them.
 */
class TicketStatusController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.statuses.index', [
            'statuses' => TicketStatus::query()->withCount('tickets')->ordered()->get(),
            'palettes' => Palette::cases(),
            'editing' => $request->integer('edit') ? TicketStatus::query()->find($request->integer('edit')) : null,
        ]);
    }

    public function update(Request $request, TicketStatus $status, RecordActivity $recordActivity): RedirectResponse
    {
        $validated = $request->validateWithBag('editStatus', [
            'name' => ['required', 'string', 'max:50', Rule::unique(TicketStatus::class)->ignore($status)],
            'color' => ['required', Rule::enum(Palette::class)],
        ]);

        $before = ['name' => $status->name, 'colour' => $status->color->label()];
        $status->update($validated);
        $after = ['name' => $status->name, 'colour' => $status->color->label()];
        $changed = array_keys(array_diff_assoc($after, $before));

        if ($changed !== []) {
            $recordActivity->handle(
                $request->user(),
                'settings.status_updated',
                "{$request->user()->name} edited the {$before['name']} status",
                $status,
                array_intersect_key($before, array_flip($changed)),
                array_intersect_key($after, array_flip($changed)),
            );
        }

        return redirect()->route('admin.statuses.index')->with('success', "Status “{$status->name}” saved.");
    }
}
