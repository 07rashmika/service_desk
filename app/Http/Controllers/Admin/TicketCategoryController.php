<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketCategoryController extends Controller
{
    /**
     * Icons admins can pick for a category (Material Symbols names).
     *
     * @var array<string, string>
     */
    public const ICONS = [
        'computer' => 'Computer',
        'apps' => 'Apps',
        'wifi' => 'Wi-Fi',
        'manage_accounts' => 'Accounts',
        'print' => 'Printer',
        'phone_iphone' => 'Phone',
        'security' => 'Security',
        'cloud' => 'Cloud',
        'storage' => 'Server',
        'videocam' => 'Video & AV',
        'badge' => 'Access card',
        'help' => 'Other',
    ];

    public function index(Request $request): View
    {
        return view('admin.categories.index', [
            'categories' => TicketCategory::query()->withCount('tickets')->ordered()->get(),
            'icons' => self::ICONS,
            'editing' => $request->integer('edit') ? TicketCategory::query()->find($request->integer('edit')) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = TicketCategory::query()->create($this->validated($request, 'createCategory'));

        return redirect()->route('admin.categories.index')->with('success', "Category “{$category->name}” added.");
    }

    public function update(Request $request, TicketCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, 'editCategory', $category));

        return redirect()->route('admin.categories.index')->with('success', "Category “{$category->name}” saved.");
    }

    /**
     * Switch a category on or off in the "Report an issue" form.
     */
    public function toggle(TicketCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', $category->is_active
            ? "“{$category->name}” is available again when reporting an issue."
            : "“{$category->name}” is hidden from the Report an issue form. Existing tickets keep it.");
    }

    public function destroy(TicketCategory $category): RedirectResponse
    {
        if ($category->tickets()->withTrashed()->exists()) {
            return back()->with('error', "“{$category->name}” is used by tickets, so it can't be deleted. Switch it off instead.");
        }

        $category->delete();

        return back()->with('success', "Category “{$category->name}” deleted.");
    }

    /**
     * @return array{name: string, description: ?string, icon: string, sort_order: int, is_active: bool}
     */
    protected function validated(Request $request, string $errorBag, ?TicketCategory $category = null): array
    {
        $validated = $request->validateWithBag($errorBag, [
            'name' => ['required', 'string', 'max:100', Rule::unique(TicketCategory::class)->ignore($category)],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', Rule::in(array_keys(self::ICONS))],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        return [...$validated, 'is_active' => $category->is_active ?? true];
    }
}
