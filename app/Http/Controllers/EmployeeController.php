<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(
            Employee::with(['user', 'department', 'designation'])->paginate()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'string', Password::defaults()],
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'joining_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Step 1 — create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => 'employee',
            ]);

            // Step 2 — create employee linked to user
            $employee = Employee::create([
                'user_id' => $user->id,
                'department_id' => $validated['department_id'],
                'designation_id' => $validated['designation_id'],
                'joining_date' => $validated['joining_date'],
                'phone' => $validated['phone'] ?? null,
                'salary' => $validated['salary'],
            ]);

            DB::commit();

            return response()->json(
                $employee->load(['user', 'department', 'designation']),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create employee.', ['exception' => $e]);

            return response()->json([
                'message' => 'Failed to create employee.',
            ], 500);
        }
    }

    public function show(Employee $employee)
    {
        return response()->json(
            $employee->load(['user', 'department', 'designation'])
        );
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,'.$employee->user_id,
            'phone' => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'joining_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            // Update user details
            $employee->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Update employee details
            $employee->update([
                'department_id' => $validated['department_id'],
                'designation_id' => $validated['designation_id'],
                'joining_date' => $validated['joining_date'],
                'phone' => $validated['phone'] ?? null,
                'salary' => $validated['salary'],
                'status' => $validated['status'] ?? $employee->status,
            ]);

            DB::commit();

            return response()->json(
                $employee->load(['user', 'department', 'designation'])
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update employee.', ['exception' => $e]);

            return response()->json([
                'message' => 'Failed to update employee.',
            ], 500);
        }
    }

    public function destroy(Employee $employee)
    {
        // Soft-delete the employee record and revoke API access without
        // removing the underlying user account (kept for audit history).
        $employee->user->tokens()->delete();
        $employee->delete();

        return response()->json(['message' => 'Employee deleted.']);
    }
}
