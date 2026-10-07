<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordActivity;
use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.departments.index', [
            'departments' => Department::query()->withCount('users')->orderBy('name')->get(),
            'editing' => $request->integer('edit') ? Department::query()->find($request->integer('edit')) : null,
        ]);
    }

    public function store(Request $request, RecordActivity $recordActivity): RedirectResponse
    {
        $validated = $request->validateWithBag('createDepartment', [
            'name' => ['required', 'string', 'max:100', Rule::unique(Department::class)],
        ]);

        $department = Department::query()->create($validated);
        $recordActivity->handle($request->user(), 'settings.department_created', "{$request->user()->name} added the {$department->name} department", $department);

        return redirect()->route('admin.departments.index')->with('success', "Department “{$validated['name']}” added.");
    }

    public function update(Request $request, Department $department, RecordActivity $recordActivity): RedirectResponse
    {
        $validated = $request->validateWithBag('editDepartment', [
            'name' => ['required', 'string', 'max:100', Rule::unique(Department::class)->ignore($department)],
        ]);

        $oldName = $department->name;
        $department->update($validated);
        $recordActivity->handle($request->user(), 'settings.department_renamed', "{$request->user()->name} renamed the {$oldName} department", $department, ['name' => $oldName], ['name' => $department->name]);

        return redirect()->route('admin.departments.index')->with('success', 'Department renamed.');
    }

    public function destroy(Request $request, Department $department, RecordActivity $recordActivity): RedirectResponse
    {
        if ($department->users()->exists()) {
            return back()->with('error', "“{$department->name}” still has people in it. Move them to another department first.");
        }

        $department->delete();
        $recordActivity->handle($request->user(), 'settings.department_deleted', "{$request->user()->name} deleted the {$department->name} department");

        return back()->with('success', "Department “{$department->name}” deleted.");
    }
}
