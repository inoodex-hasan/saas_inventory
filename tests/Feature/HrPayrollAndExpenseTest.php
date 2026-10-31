<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payroll;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HrPayrollAndExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private ExpenseCategory $category;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->user->current_team_id = $this->team->id;
        $this->user->save();
        $this->actingAs($this->user);

        $this->category = ExpenseCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Office Utilities & Internet',
            'code' => 'UTIL',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'team_id' => $this->team->id,
            'employee_code' => 'EMP-0001',
            'name' => 'Rafiqul Islam',
            'email' => 'rafiq@company.com',
            'phone' => '+880 1711 000111',
            'designation' => 'Senior Field Engineer',
            'department' => 'Operations',
            'joining_date' => '2025-01-15',
            'basic_salary' => 45000,
            'bank_name' => 'BRAC Bank',
            'bank_account_no' => '150120334455',
            'status' => 'active',
        ]);
    }

    public function test_user_can_view_employees_directory_and_filter(): void
    {
        $response = $this->get(route('employees.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.name', 'Rafiqul Islam')
            ->where('metrics.total_employees', 1)
            ->where('metrics.active_employees', 1)
            ->has('departments')
        );
    }

    public function test_user_can_create_employee(): void
    {
        $response = $this->post(route('employees.store'), [
            'name' => 'Sumaiya Akter',
            'designation' => 'Accounts Executive',
            'department' => 'Finance',
            'phone' => '+880 1811 222333',
            'email' => 'sumaiya@company.com',
            'joining_date' => now()->toDateString(),
            'basic_salary' => 38000,
            'status' => 'active',
        ]);

        $response->assertSessionHas('success');

        $emp = Employee::where('name', 'Sumaiya Akter')->first();
        $this->assertNotNull($emp);
        $this->assertEquals($this->team->id, $emp->team_id);
        $this->assertStringStartsWith('EMP-', $emp->employee_code);
        $this->assertEquals(38000, (float) $emp->basic_salary);
    }

    public function test_user_can_update_and_delete_employee(): void
    {
        // Update
        $updateResp = $this->put(route('employees.update', $this->employee->id), [
            'name' => 'Rafiqul Islam (Lead)',
            'designation' => 'Principal Field Engineer',
            'department' => 'Operations',
            'basic_salary' => 52000,
            'status' => 'active',
        ]);
        $updateResp->assertSessionHas('success');

        $this->employee->refresh();
        $this->assertEquals('Rafiqul Islam (Lead)', $this->employee->name);
        $this->assertEquals(52000, (float) $this->employee->basic_salary);

        // Delete
        $delResp = $this->delete(route('employees.destroy', $this->employee->id));
        $delResp->assertSessionHas('success');

        $this->assertSoftDeleted('employees', ['id' => $this->employee->id]);
    }

    public function test_user_can_record_and_filter_daily_expenses(): void
    {
        $response = $this->post(route('expenses.store'), [
            'title' => 'High-Speed Fiber Lease Bill',
            'amount' => 4500,
            'expense_category_id' => $this->category->id,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'vendor_or_payee' => 'Dot Internet Ltd',
            'receipt_reference' => 'BILL-8822',
        ]);

        $response->assertSessionHas('success');

        $expense = Expense::where('title', 'High-Speed Fiber Lease Bill')->first();
        $this->assertNotNull($expense);
        $this->assertEquals($this->team->id, $expense->team_id);
        $this->assertEquals(4500, (float) $expense->amount);
        $this->assertStringStartsWith('EXP-', $expense->expense_number);

        // Index view check
        $indexResp = $this->get(route('expenses.index'));
        $indexResp->assertStatus(200);
        $indexResp->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HR/Expenses/Index')
            ->has('expenses.data', 1)
            ->where('expenses.data.0.amount', '4500.00')
        );
    }

    public function test_user_can_generate_payroll_with_allowances_and_deductions(): void
    {
        // Generate payroll: Basic 45,000 + Bonus 2,000 - Advance 1,000 = 46,000
        $response = $this->post(route('payroll.generate'), [
            'employee_id' => $this->employee->id,
            'month_year' => '2026-10',
            'other_allowances' => 2000,
            'deductions_total' => 1000,
        ]);

        $response->assertSessionHas('success');

        $payroll = Payroll::where('employee_id', $this->employee->id)
            ->where('month_year', '2026-10')
            ->first();

        $this->assertNotNull($payroll);
        $this->assertEquals(45000, (float) $payroll->basic_salary);
        $this->assertEquals(2000, (float) $payroll->other_allowances);
        $this->assertEquals(1000, (float) $payroll->deductions_total);
        $this->assertEquals(46000, (float) $payroll->net_salary);
        $this->assertEquals('draft', $payroll->status);
    }

    public function test_cannot_generate_duplicate_payroll_for_same_month_and_employee(): void
    {
        Payroll::create([
            'team_id' => $this->team->id,
            'employee_id' => $this->employee->id,
            'payroll_number' => 'PAY-202610-0001',
            'month_year' => '2026-10',
            'basic_salary' => 45000,
            'net_salary' => 45000,
            'status' => 'draft',
        ]);

        $response = $this->post(route('payroll.generate'), [
            'employee_id' => $this->employee->id,
            'month_year' => '2026-10',
        ]);

        $response->assertSessionHasErrors('employee_id');
    }

    public function test_user_can_mark_payroll_as_disbursed(): void
    {
        $payroll = Payroll::create([
            'team_id' => $this->team->id,
            'employee_id' => $this->employee->id,
            'payroll_number' => 'PAY-202610-0002',
            'month_year' => '2026-10',
            'basic_salary' => 45000,
            'net_salary' => 45000,
            'status' => 'draft',
        ]);

        $response = $this->post(route('payroll.pay', $payroll->id), [
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'NPSB-99882211',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHas('success');

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->status);
        $this->assertEquals('bank_transfer', $payroll->payment_method);
        $this->assertEquals('NPSB-99882211', $payroll->payment_reference);
    }

    public function test_user_can_generate_salary_slip_pdf(): void
    {
        $payroll = Payroll::create([
            'team_id' => $this->team->id,
            'employee_id' => $this->employee->id,
            'payroll_number' => 'PAY-202610-0003',
            'month_year' => '2026-10',
            'basic_salary' => 45000,
            'tada_allowance' => 2000,
            'net_salary' => 47000,
            'status' => 'paid',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $response = $this->get(route('payroll.pdf', $payroll->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_tenant_isolation_protects_hr_and_payroll_data(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $otherEmployee = Employee::create([
            'team_id' => $otherTeam->id,
            'employee_code' => 'EMP-9999',
            'name' => 'Secret Spy',
            'designation' => 'Operative',
            'basic_salary' => 99999,
        ]);

        $otherPayroll = Payroll::create([
            'team_id' => $otherTeam->id,
            'employee_id' => $otherEmployee->id,
            'payroll_number' => 'PAY-202610-9999',
            'month_year' => '2026-10',
            'basic_salary' => 99999,
            'net_salary' => 99999,
            'status' => 'paid',
        ]);

        // Attempting to access from $this->user's current team
        $response = $this->get(route('payroll.pdf', $otherPayroll->id));
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
