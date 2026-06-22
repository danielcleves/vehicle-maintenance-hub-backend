<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Auth\Controllers\AuthController;
use App\Domains\Vehicles\Controllers\VehicleController;
use App\Domains\Maintenance\Controllers\MaintenancePlanController;
use App\Domains\Maintenance\Controllers\MaintenanceRecordController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('vehicles', VehicleController::class)->except(['show']);
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::patch('vehicles/{vehicle}/mileage', [VehicleController::class, 'updateMileage']);
    Route::get('vehicles/{vehicle}/reminders', [VehicleController::class, 'reminders']);
    Route::post('vehicles/{vehicle}/assign-plan', [VehicleController::class, 'assignPlan']);
    Route::post('vehicles/{vehicle}/copy-plan', [VehicleController::class, 'copyPlan']);
    Route::get('vehicles/{vehicle}/export', [VehicleController::class, 'export']);
    Route::post('vehicles/import', [VehicleController::class, 'import']);

    Route::apiResource('maintenance-plans', MaintenancePlanController::class);
    Route::put('maintenance-plans/{plan}/sync-tasks', [MaintenancePlanController::class, 'syncTasks']);
    Route::post('maintenance-plans/{plan}/convert', [MaintenancePlanController::class, 'convert']);

    Route::get('vehicles/{vehicle}/records', [MaintenanceRecordController::class, 'index']);
    Route::post('vehicles/{vehicle}/records', [MaintenanceRecordController::class, 'store']);
    Route::get('vehicles/{vehicle}/records/{record}', [MaintenanceRecordController::class, 'show']);
    Route::put('vehicles/{vehicle}/records/{record}', [MaintenanceRecordController::class, 'update']);
    Route::patch('vehicles/{vehicle}/records/{record}', [MaintenanceRecordController::class, 'update']);
    Route::delete('vehicles/{vehicle}/records/{record}', [MaintenanceRecordController::class, 'destroy']);
});
