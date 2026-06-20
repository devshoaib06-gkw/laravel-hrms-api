<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'employee') {
            return response()->json(
                $user->employee->leaves()->latest()->get()
            );
        }

        return response()->json(Leave::with('employee.user')->latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|max:255',
        ]);

        $employee = auth()->user()->employee;
        $leave = $employee->leaves()->create([
            ...$validated,
            'status' => 'pending',
        ]);

        return response()->json($leave, 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(Leave $leave)
    {
        return response()->json($leave->load('employee.user'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $leave->update($validated);

        return response()->json($leave->load('employee.user'));
    }


}
