<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // Admin/HR — view all attendance
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'employee') {
            return response()->json(
                $user->employee->attendances()->latest('date')->get()
            );
        }

        return response()->json(
            Attendance::with('employee.user')->latest('date')->get()
        );
    }

    // Employee — check in
    public function checkIn(Request $request)
    {
        $employee = auth()->user()->employee;
        $today = Carbon::today()->toDateString();

        $existing = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Already checked in today.',
            ], 422);
        }

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => $today,
            'clock_in' => Carbon::now(),
            'status' => 'present',
        ]);

        return response()->json($attendance, 201);
    }

    // Employee — check out
    public function checkOut(Request $request)
    {
        $employee = auth()->user()->employee;
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (! $attendance) {
            return response()->json([
                'message' => 'No check-in found for today.',
            ], 422);
        }

        if ($attendance->clock_out) {
            return response()->json([
                'message' => 'Already checked out today.',
            ], 422);
        }

        $attendance->update(['clock_out' => Carbon::now()]);

        return response()->json($attendance);
    }
}
