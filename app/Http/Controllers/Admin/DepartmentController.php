<?php

namespace App\Http\Controllers\Admin;

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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createDepartment', [
            'name' => ['required', 'string', 'max:100', Rule::unique(Department::class)],
        ]);

        Department::query()->create($validated);

        return redirect()->route('admin.departments.index')->with('success', "Department “{$validated['name']}” added.");
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validateWithBag('editDepartment', [
            'name' => ['required', 'string', 'max:100', Rule::unique(Department::class)->ignore($department)],
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')->with('success', 'Department renamed.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->users()->exists()) {
            return back()->with('error', "“{$department->name}” still has people in it. Move them to another department first.");
        }

        $department->delete();

        return back()->with('success', "Department “{$department->name}” deleted.");
    }
}
