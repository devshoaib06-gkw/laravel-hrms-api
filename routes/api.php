<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
    });

    Route::middleware('auth:sanctum')->group(function () {


        // ── Admin only ──
        Route::middleware('role:admin')->group(function () {
            // Route::apiResource('departments', DepartmentController::class);
            // Route::apiResource('designations', DesignationController::class);
        });

        // ── Admin + HR Manager ──
        Route::middleware('role:admin,hr_manager')->group(function () {
            // Route::apiResource('employees', EmployeeController::class);
            // Route::apiResource('payrolls', PayrollController::class);
        });

        // ── Admin + HR Manager + Employee ──
        Route::middleware('role:admin,hr_manager,employee')->group(function () {
            // Route::apiResource('leaves', LeaveController::class);
            // Route::apiResource('attendances', AttendanceController::class);
        });

    });
});
