<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TaDaClaim;
use App\Services\HrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TaDaClaimController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $employeeId = $request->input('employee_id', '');

        $query = TaDaClaim::where('team_id', $teamId)
            ->with(['employee', 'approver', 'creator'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('claim_number', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($employeeId !== '', fn ($q) => $q->where('employee_id', $employeeId));

        $claims = $query->latest('travel_date')->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_claims' => TaDaClaim::where('team_id', $teamId)->count(),
            'pending_approval' => TaDaClaim::where('team_id', $teamId)->where('status', 'pending')->count(),
            'approved_unreimbursed' => (float) TaDaClaim::where('team_id', $teamId)->where('status', 'approved')->sum('total_amount'),
            'reimbursed_total' => (float) TaDaClaim::where('team_id', $teamId)->where('status', 'reimbursed')->sum('total_amount'),
        ];

        $employees = Employee::where('team_id', $teamId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_code', 'designation']);

        return Inertia::render('HR/TaDa/Index', [
            'page_title' => 'Field Travel & Daily Allowance (TA/DA)',
            'claims' => $claims,
            'metrics' => $metrics,
            'employees' => $employees,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'employee_id' => $employeeId,
            ],
        ]);
    }

    public function store(Request $request, HrService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('team_id', $teamId)],
            'travel_date' => 'required|date',
            'destination' => 'required|string|max:255',
            'purpose' => 'required|string|max:500',
            'travel_allowance' => 'nullable|numeric|min:0',
            'daily_allowance' => 'nullable|numeric|min:0',
            'other_expenses' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $claim = $service->submitTaDaClaim($validated);

        return back()->with('success', "TA/DA Claim {$claim->claim_number} submitted successfully.");
    }

    public function approve(Request $request, TaDaClaim $claim, HrService $service)
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        abort_if($claim->team_id !== $team->id, 403);

        if (!$user->isSuperAdmin() && !$user->ownsTeam($team) && !$user->hasTeamRole($team, 'admin')) {
            abort(403, 'Unauthorized. Only team owners and administrators can approve TA/DA claims.');
        }

        $claim->loadMissing('employee');
        if ($claim->employee && $claim->employee->user_id && (int) $claim->employee->user_id === (int) $user->id) {
            abort(403, 'You cannot approve your own TA/DA claim.');
        }

        $service->approveTaDaClaim($claim, $user->id);

        return back()->with('success', "Claim {$claim->claim_number} approved. Ready for reimbursement in payroll.");
    }

    public function reject(Request $request, TaDaClaim $claim, HrService $service)
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        abort_if($claim->team_id !== $team->id, 403);

        if (!$user->isSuperAdmin() && !$user->ownsTeam($team) && !$user->hasTeamRole($team, 'admin')) {
            abort(403, 'Unauthorized. Only team owners and administrators can reject TA/DA claims.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $service->rejectTaDaClaim($claim, $validated['rejection_reason'], $user->id);

        return back()->with('success', "Claim {$claim->claim_number} has been rejected.");
    }

    public function destroy(TaDaClaim $claim)
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        abort_if($claim->team_id !== $team->id, 403);

        if (!$user->isSuperAdmin() && !$user->ownsTeam($team) && !$user->hasTeamRole($team, 'admin')) {
            abort(403, 'Unauthorized. Only team owners and administrators can delete TA/DA claims.');
        }

        $num = $claim->claim_number;
        $claim->delete();

        return back()->with('success', "Claim {$num} deleted.");
    }
}
