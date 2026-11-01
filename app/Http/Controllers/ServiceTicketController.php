<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\FinancialAccount;
use App\Services\FinancialAccountService;
use App\Services\ServiceTicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ServiceTicketController extends Controller
{
    public function index(Request $request, FinancialAccountService $accountService)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $status = $request->input('status');
        $priority = $request->input('priority');
        $technicianId = $request->input('technician_id');
        $paymentStatus = $request->input('payment_status');

        $query = ServiceTicket::where('team_id', $teamId)
            ->with(['customer', 'technician'])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('device_name', 'like', "%{$search}%")
                        ->orWhere('serial_no', 'like', "%{$search}%")
                        ->orWhere('technician_name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $status))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $priority))
            ->when($request->filled('technician_id'), fn ($q) => $q->where('technician_id', $technicianId))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $paymentStatus));

        $tickets = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_tickets' => ServiceTicket::where('team_id', $teamId)->count(),
            'in_progress' => ServiceTicket::where('team_id', $teamId)->whereIn('status', ['received', 'diagnosing', 'waiting_parts'])->count(),
            'repaired' => ServiceTicket::where('team_id', $teamId)->where('status', 'repaired')->count(),
            'delivered' => ServiceTicket::where('team_id', $teamId)->where('status', 'delivered')->count(),
            'total_revenue' => (float) ServiceTicket::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_dues' => (float) ServiceTicket::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('due_amount'),
        ];

        $teamUsers = Auth::user()->currentTeam ? Auth::user()->currentTeam->allUsers() : collect([Auth::user()]);
        $accountService->ensureDefaultAccounts($teamId);
        $financialAccounts = $accountService->getActiveAccountsForTeam($teamId);

        return Inertia::render('Services/Index', [
            'page_title' => 'Workshop & Repair Service Jobs',
            'tickets' => $tickets,
            'metrics' => $metrics,
            'technicians' => $teamUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
            'financialAccounts' => $financialAccounts,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'priority' => $priority,
                'technician_id' => $technicianId,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    public function create()
    {
        $teamId = Auth::user()->current_team_id;

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $prefix = 'SRV-' . Carbon::now()->format('Ymd') . '-';
        $last = ServiceTicket::where('team_id', $teamId)->where('ticket_number', 'LIKE', $prefix . '%')->latest('id')->first();
        $nextNum = $last ? str_pad((int) substr($last->ticket_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
        $nextTicketNumber = $prefix . $nextNum;

        $teamUsers = Auth::user()->currentTeam ? Auth::user()->currentTeam->allUsers() : collect([Auth::user()]);

        return Inertia::render('Services/Create', [
            'page_title' => 'Intake New Service Job',
            'nextTicketNumber' => $nextTicketNumber,
            'customers' => $customers,
            'products' => $products,
            'warehouses' => $warehouses,
            'technicians' => $teamUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
            'todayDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, ServiceTicketService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $team = Auth::user()->currentTeam;
        $allowedTechnicianIds = $team ? $team->allUsers()->pluck('id')->all() : [Auth::id()];

        $rules = [
            'customer_mode' => 'required|in:existing,new',
            'device_name' => 'required|string|max:255',
            'device_brand' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'accessories_received' => 'nullable|string|max:255',
            'problem_description' => 'nullable|string|max:2000',
            'initial_diagnosis' => 'nullable|string|max:2000',
            'technician_id' => ['nullable', Rule::in($allowedTechnicianIds)],
            'technician_name' => 'nullable|string|max:100',
            'priority' => 'required|in:low,normal,high,urgent',
            'service_charge' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'warranty_days' => 'nullable|integer|min:0',
            'received_date' => 'required|date',
            'estimated_completion_date' => 'nullable|date|after_or_equal:received_date',
            'remarks' => 'nullable|string|max:1000',
            'parts' => 'nullable|array',
            'parts.*.part_name' => 'required|string|max:255',
            'parts.*.quantity' => 'required|numeric|min:0.01',
            'parts.*.unit_price' => 'required|numeric|min:0',
            'parts.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'parts.*.warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'parts.*.notes' => 'nullable|string|max:255',
        ];

        if ($request->input('customer_mode') === 'new') {
            $rules['new_customer_name'] = 'required|string|max:255';
            $rules['new_customer_phone'] = 'required|string|max:50';
            $rules['new_customer_email'] = 'nullable|email|max:255';
            $rules['new_customer_address'] = 'nullable|string|max:500';
        } else {
            $rules['customer_id'] = ['required', Rule::exists('customers', 'id')->where('team_id', $teamId)];
        }

        $validated = $request->validate($rules);

        $ticket = $service->createTicket($validated, $request->input('parts', []));

        return redirect()->route('services.show', $ticket->id)->with('success', "Service job ticket {$ticket->ticket_number} created successfully.");
    }

    public function show(ServiceTicket $ticket, FinancialAccountService $accountService)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($ticket->team_id !== $teamId, 403);

        $ticket->load(['customer', 'technician', 'creator', 'items.product', 'items.warehouse']);

        $products = Product::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $teamUsers = Auth::user()->currentTeam ? Auth::user()->currentTeam->allUsers() : collect([Auth::user()]);

        $accountService->ensureDefaultAccounts($teamId);
        $financialAccounts = $accountService->getActiveAccountsForTeam($teamId);

        return Inertia::render('Services/Show', [
            'page_title' => "Service Ticket #{$ticket->ticket_number}",
            'ticket' => $ticket,
            'products' => $products,
            'warehouses' => $warehouses,
            'technicians' => $teamUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
            'financialAccounts' => $financialAccounts,
        ]);
    }

    public function edit(ServiceTicket $ticket)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($ticket->team_id !== $teamId, 403);

        $ticket->load(['customer', 'technician']);
        $teamUsers = Auth::user()->currentTeam ? Auth::user()->currentTeam->allUsers() : collect([Auth::user()]);

        return Inertia::render('Services/Edit', [
            'page_title' => "Edit Service Ticket #{$ticket->ticket_number}",
            'ticket' => $ticket,
            'technicians' => $teamUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function update(Request $request, ServiceTicket $ticket, ServiceTicketService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($ticket->team_id !== $teamId, 403);

        $team = Auth::user()->currentTeam;
        $allowedTechnicianIds = $team ? $team->allUsers()->pluck('id')->all() : [Auth::id()];

        $validated = $request->validate([
            'device_name' => 'required|string|max:255',
            'device_brand' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'accessories_received' => 'nullable|string|max:255',
            'problem_description' => 'nullable|string|max:2000',
            'initial_diagnosis' => 'nullable|string|max:2000',
            'technician_id' => ['nullable', Rule::in($allowedTechnicianIds)],
            'technician_name' => 'nullable|string|max:100',
            'priority' => 'required|in:low,normal,high,urgent',
            'service_charge' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'warranty_days' => 'nullable|integer|min:0',
            'estimated_completion_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $ticket->update([
            'device_name' => $validated['device_name'],
            'device_brand' => $validated['device_brand'] ?? null,
            'serial_no' => $validated['serial_no'] ?? null,
            'accessories_received' => $validated['accessories_received'] ?? null,
            'problem_description' => $validated['problem_description'] ?? null,
            'initial_diagnosis' => $validated['initial_diagnosis'] ?? null,
            'technician_id' => $validated['technician_id'] ?? null,
            'technician_name' => $validated['technician_name'] ?? null,
            'priority' => $validated['priority'],
            'service_charge' => max(0, (float) ($validated['service_charge'] ?? 0)),
            'discount_amount' => max(0, (float) ($validated['discount_amount'] ?? 0)),
            'warranty_days' => max(0, (int) ($validated['warranty_days'] ?? 0)),
            'estimated_completion_date' => $validated['estimated_completion_date'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $service->recalculateTicketTotals($ticket);

        return redirect()->route('services.show', $ticket->id)->with('success', "Service job ticket {$ticket->ticket_number} updated successfully.");
    }

    public function addPart(Request $request, ServiceTicket $ticket, ServiceTicketService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($ticket->team_id !== $teamId, 403);

        $validated = $request->validate([
            'part_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'product_id' => ['nullable', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'notes' => 'nullable|string|max:255',
        ]);

        $service->addPart($ticket, $validated);

        return redirect()->back()->with('success', "Spare part added to ticket #{$ticket->ticket_number}.");
    }

    public function removePart(ServiceTicket $ticket, ServiceTicketItem $item, ServiceTicketService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($ticket->team_id !== $teamId || $item->service_ticket_id !== $ticket->id, 403);

        $service->removePart($ticket, $item);

        return redirect()->back()->with('success', "Spare part removed and stock adjusted.");
    }

    public function transitionStatus(Request $request, ServiceTicket $ticket, ServiceTicketService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($ticket->team_id !== $teamId, 403);

        $validated = $request->validate([
            'status' => 'required|in:received,diagnosing,waiting_parts,repaired,delivered,cancelled',
        ]);

        $service->transitionStatus($ticket, $validated['status']);

        return redirect()->back()->with('success', "Ticket #{$ticket->ticket_number} moved to status: {$validated['status']}.");
    }

    public function recordPayment(Request $request, ServiceTicket $ticket, ServiceTicketService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($ticket->team_id !== $teamId, 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $service->recordPayment(
            $ticket,
            (float) $validated['amount'],
            $validated['payment_method'] ?? null,
            $validated['notes'] ?? null,
            $validated['financial_account_id'] ?? null
        );

        return redirect()->back()->with('success', "Payment of {$validated['amount']} recorded for ticket #{$ticket->ticket_number}.");
    }

    public function destroy(ServiceTicket $ticket)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($ticket->team_id !== $teamId, 403);

        $ticket->delete();

        return redirect()->route('services.index')->with('success', "Service ticket #{$ticket->ticket_number} deleted successfully.");
    }
}
