<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'employee') {
            if (! $user->employee) {
                return response()->json([]);
            }

            return response()->json(
                $user->employee->payrolls()->latest()->get()
            );
        }

        return response()->json(
            Payroll::with('employee.user', 'approver')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'working_days' => 'required|integer|min:1',
            'deductions' => 'nullable|numeric|min:0',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        // Check duplicate
        $exists = Payroll::where('employee_id', $employee->id)
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Payroll already generated for this month.',
            ], 422);
        }

        // Auto-calculate present_days from attendance table
        $presentDays = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $validated['month'])
            ->whereYear('date', $validated['year'])
            ->where('status', 'present')
            ->count();

        $deductions = $validated['deductions'] ?? 0;

        // Pro-rate salary based on present days
        $perDaySalary = $employee->salary / $validated['working_days'];
        $earnedSalary = $perDaySalary * $presentDays;
        $netSalary = $earnedSalary - $deductions;

        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'month' => $validated['month'],
            'year' => $validated['year'],
            'basic_salary' => $employee->salary,
            'working_days' => $validated['working_days'],
            'present_days' => $presentDays,
            'deductions' => $deductions,
            'net_salary' => round($netSalary, 2),
            'status' => 'draft',
        ]);

        return response()->json(
            $payroll->load('employee.user'),
            201
        );
    }

    // Approve payroll — admin/hr only
    public function update(Request $request, Payroll $payroll)
    {
        $request->validate([
            'status' => 'required|in:draft,approved,paid',
        ]);

        $transitions = [
            'draft' => ['approved'],
            'approved' => ['paid'],
            'paid' => [],
        ];

        if (! in_array($request->status, $transitions[$payroll->status])) {
            return response()->json([
                'message' => "Cannot transition from '{$payroll->status}' to '{$request->status}'.",
            ], 422);
        }

        $payroll->update([
            'status' => $request->status,
            'approved_by' => auth()->id(),
        ]);

        return response()->json($payroll->load('employee.user', 'approver'));
    }

    public function show(Payroll $payroll)
    {
        return response()->json($payroll->load('employee.user', 'approver'));
    }
}
