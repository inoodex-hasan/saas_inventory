<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\HrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $status = $request->input('status');
        $department = $request->input('department');

        $query = Employee::where('team_id', $teamId)
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $status))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $department));

        $employees = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_employees' => Employee::where('team_id', $teamId)->count(),
            'active_employees' => Employee::where('team_id', $teamId)->where('status', 'active')->count(),
            'total_payroll_cost' => (float) Employee::where('team_id', $teamId)->where('status', 'active')->sum('basic_salary'),
        ];

        $departments = Employee::where('team_id', $teamId)
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        return Inertia::render('HR/Employees/Index', [
            'page_title' => 'Staff & Employee Directory',
            'employees' => $employees,
            'metrics' => $metrics,
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'department' => $department,
            ],
        ]);
    }

    public function store(Request $request, HrService $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'employee_code' => 'nullable|string|max:50',
            'designation' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'joining_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:100',
            'mobile_banking_no' => 'nullable|string|max:50',
            'status' => 'required|in:active,on_leave,terminated',
            'notes' => 'nullable|string|max:1000',
        ]);

        $employee = $service->createEmployee($validated);

        return back()->with('success', "Employee {$employee->name} ({$employee->employee_code}) added successfully.");
    }

    public function update(Request $request, Employee $employee, HrService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($employee->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'joining_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:100',
            'mobile_banking_no' => 'nullable|string|max:50',
            'status' => 'required|in:active,on_leave,terminated',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service->updateEmployee($employee, $validated);

        return back()->with('success', "Employee {$employee->name} updated successfully.");
    }

    public function destroy(Employee $employee)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($employee->team_id !== $teamId, 403);

        $name = $employee->name;
        $employee->delete();

        return back()->with('success', "Employee {$name} deleted successfully.");
    }
}
