<?php

namespace App\Domains\Vehicles\Controllers;

use App\Domains\Maintenance\Models\MaintenancePlan;
use App\Domains\Maintenance\Models\MaintenancePlanTask;
use App\Domains\Maintenance\Models\MaintenanceRecord;
use App\Domains\Vehicles\Models\Vehicle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = auth()->user()->vehicles()->with('maintenancePlan.tasks', 'maintenanceRecords')->get();

        $vehicles = $vehicles->map(function ($vehicle) {
            $user = auth()->user();
            $records = $vehicle->maintenanceRecords;

            $overdue = 0;
            $upcoming = 0;

            if ($vehicle->maintenancePlan) {
                foreach ($vehicle->maintenancePlan->tasks as $task) {
                    $lastRecord = $records
                        ->where('maintenance_plan_task_id', $task->id)
                        ->sortByDesc('date')
                        ->first();

                    $lastMileage = $lastRecord?->mileage ?? $vehicle->mileage;
                    $lastDate = $lastRecord?->date ?? $vehicle->created_at;

                    $nextDueKm = $task->frequency_km ? $lastMileage + $task->frequency_km : null;
                    $nextDueDate = $task->frequency_time_months ? $lastDate->copy()->addMonths($task->frequency_time_months) : null;

                    if (($nextDueKm && $vehicle->mileage >= $nextDueKm) || ($nextDueDate && now()->startOfDay()->greaterThanOrEqualTo($nextDueDate))) {
                        $overdue++;
                    } elseif (
                        ($nextDueKm && $vehicle->mileage >= ($nextDueKm - $user->advance_alerts_mileage)) ||
                        ($nextDueDate && now()->startOfDay()->greaterThanOrEqualTo($nextDueDate->copy()->subDays($user->advance_alerts_time)))
                    ) {
                        $upcoming++;
                    }

                    $nextReviewKm = $task->review_frequency_km ? $lastMileage + $task->review_frequency_km : null;
                    $nextReviewDate = $task->review_frequency_time_months ? $lastDate->copy()->addMonths($task->review_frequency_time_months) : null;

                    if (($nextReviewKm && $vehicle->mileage >= $nextReviewKm) || ($nextReviewDate && now()->startOfDay()->greaterThanOrEqualTo($nextReviewDate))) {
                        $overdue++;
                    } elseif (
                        ($nextReviewKm && $vehicle->mileage >= ($nextReviewKm - $user->advance_alerts_mileage)) ||
                        ($nextReviewDate && now()->startOfDay()->greaterThanOrEqualTo($nextReviewDate->copy()->subDays($user->advance_alerts_time)))
                    ) {
                        $upcoming++;
                    }
                }
            }

            $vehicleArray = $vehicle->toArray();
            $vehicleArray['overdue_count'] = $overdue;
            $vehicleArray['upcoming_count'] = $upcoming;
            unset($vehicleArray['maintenance_records']);

            return $vehicleArray;
        });

        return response()->json($vehicles);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nickname' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:2099',
            'mileage' => 'required|integer|min:0',
            'plate' => 'nullable|string|max:20',
        ]);

        $data['user_id'] = auth()->id();
        $vehicle = Vehicle::create($data);

        return response()->json($vehicle, 201);
    }

    public function show(Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $vehicle->load('maintenancePlan.tasks', 'maintenanceRecords');

        $records = $vehicle->maintenanceRecords;
        $user = auth()->user();

        $planData = null;

        if ($vehicle->maintenancePlan) {
            $plan = $vehicle->maintenancePlan;
            $planData = $plan->toArray();
            $planData['tasks'] = $plan->tasks->map(function ($task) use ($records, $vehicle, $user) {
                $taskArray = $task->toArray();

                $lastRecord = $records
                    ->where('maintenance_plan_task_id', $task->id)
                    ->sortByDesc('date')
                    ->first();

                $lastMileage = $lastRecord?->mileage ?? $vehicle->mileage;
                $lastDate = $lastRecord?->date ?? $vehicle->created_at;

                $nextDueKm = $task->frequency_km
                    ? $lastMileage + $task->frequency_km
                    : null;

                $nextDueDate = $task->frequency_time_months
                    ? $lastDate->copy()->addMonths($task->frequency_time_months)
                    : null;

                $remainingKm = $nextDueKm !== null ? max(0, $nextDueKm - $vehicle->mileage) : null;
                $remainingDays = $nextDueDate !== null ? max(0, now()->startOfDay()->diffInDays($nextDueDate, false)) : null;

                $status = 'ok';
                if ($nextDueKm && $vehicle->mileage >= $nextDueKm) {
                    $status = 'overdue';
                } elseif ($nextDueDate && now()->startOfDay()->greaterThanOrEqualTo($nextDueDate)) {
                    $status = 'overdue';
                } elseif ($nextDueKm && $vehicle->mileage >= ($nextDueKm - $user->advance_alerts_mileage)) {
                    $status = 'upcoming';
                } elseif ($nextDueDate && now()->startOfDay()->greaterThanOrEqualTo(
                    $nextDueDate->copy()->subDays($user->advance_alerts_time)
                )) {
                    $status = 'upcoming';
                }

                $taskArray['next_maintenance'] = [
                    'next_due_km' => $nextDueKm,
                    'next_due_date' => $nextDueDate?->toDateString(),
                    'remaining_km' => $remainingKm,
                    'remaining_days' => $remainingDays,
                    'status' => $status,
                ];

                $nextReviewKm = $task->review_frequency_km
                    ? $lastMileage + $task->review_frequency_km
                    : null;

                $nextReviewDate = $task->review_frequency_time_months
                    ? $lastDate->copy()->addMonths($task->review_frequency_time_months)
                    : null;

                $reviewRemainingKm = $nextReviewKm !== null ? max(0, $nextReviewKm - $vehicle->mileage) : null;
                $reviewRemainingDays = $nextReviewDate !== null ? max(0, now()->startOfDay()->diffInDays($nextReviewDate, false)) : null;

                $reviewStatus = 'ok';
                if ($nextReviewKm && $vehicle->mileage >= $nextReviewKm) {
                    $reviewStatus = 'overdue';
                } elseif ($nextReviewDate && now()->startOfDay()->greaterThanOrEqualTo($nextReviewDate)) {
                    $reviewStatus = 'overdue';
                } elseif ($nextReviewKm && $vehicle->mileage >= ($nextReviewKm - $user->advance_alerts_mileage)) {
                    $reviewStatus = 'upcoming';
                } elseif ($nextReviewDate && now()->startOfDay()->greaterThanOrEqualTo(
                    $nextReviewDate->copy()->subDays($user->advance_alerts_time)
                )) {
                    $reviewStatus = 'upcoming';
                }

                $taskArray['next_review'] = [
                    'next_due_km' => $nextReviewKm,
                    'next_due_date' => $nextReviewDate?->toDateString(),
                    'remaining_km' => $reviewRemainingKm,
                    'remaining_days' => $reviewRemainingDays,
                    'status' => $reviewStatus,
                ];

                return $taskArray;
            })->toArray();
        }

        $response = $vehicle->toArray();
        $response['maintenance_plan'] = $planData;
        $response['maintenance_records'] = $vehicle->maintenanceRecords->toArray();

        return response()->json($response);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'nickname' => 'sometimes|string|max:255',
            'brand' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:1900|max:2099',
            'mileage' => 'sometimes|integer|min:0',
            'plate' => 'nullable|string|max:20',
        ]);

        $vehicle->update($data);

        return response()->json($vehicle);
    }

    public function updateMileage(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'mileage' => 'required|integer|min:0',
        ]);

        $vehicle->update($data);

        return response()->json($vehicle);
    }

    public function destroy(Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted']);
    }

    public function reminders(Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $plan = $vehicle->maintenancePlan;

        if (! $plan) {
            return response()->json([]);
        }

        $tasks = $plan->tasks()->where('is_active', true)->get();
        $records = $vehicle->maintenanceRecords;
        $user = auth()->user();

        $reminders = $tasks->map(function ($task) use ($records, $vehicle, $user) {
            $lastRecord = $records
                ->where('maintenance_plan_task_id', $task->id)
                ->sortByDesc('date')
                ->first();

            $lastMileage = $lastRecord?->mileage ?? $vehicle->mileage;
            $lastDate = $lastRecord?->date ?? $vehicle->created_at;

            $nextDueKm = $task->frequency_km
                ? $lastMileage + $task->frequency_km
                : null;

            $nextDueDate = $task->frequency_time_months
                ? $lastDate->copy()->addMonths($task->frequency_time_months)
                : null;

            $status = 'ok';
            $isDue = false;

            if ($nextDueKm && $vehicle->mileage >= $nextDueKm) {
                $status = 'overdue';
                $isDue = true;
            } elseif ($nextDueDate && now()->startOfDay()->greaterThanOrEqualTo($nextDueDate)) {
                $status = 'overdue';
                $isDue = true;
            } elseif ($nextDueKm && $vehicle->mileage >= ($nextDueKm - $user->advance_alerts_mileage)) {
                $status = 'upcoming';
            } elseif ($nextDueDate && now()->startOfDay()->greaterThanOrEqualTo(
                $nextDueDate->copy()->subDays($user->advance_alerts_time)
            )) {
                $status = 'upcoming';
            }

            return [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'description' => $task->description,
                'frequency_km' => $task->frequency_km,
                'frequency_time_months' => $task->frequency_time_months,
                'next_due_km' => $nextDueKm,
                'next_due_date' => $nextDueDate?->toDateString(),
                'status' => $status,
                'last_record' => $lastRecord ? [
                    'date' => $lastRecord->date->toDateString(),
                    'mileage' => $lastRecord->mileage,
                ] : null,
            ];
        });

        $reviewReminders = $tasks->map(function ($task) use ($records, $vehicle, $user) {
            $lastRecord = $records
                ->where('maintenance_plan_task_id', $task->id)
                ->sortByDesc('date')
                ->first();

            $lastMileage = $lastRecord?->mileage ?? $vehicle->mileage;
            $lastDate = $lastRecord?->date ?? $vehicle->created_at;

            $nextDueKm = $task->review_frequency_km
                ? $lastMileage + $task->review_frequency_km
                : null;

            $nextDueDate = $task->review_frequency_time_months
                ? $lastDate->copy()->addMonths($task->review_frequency_time_months)
                : null;

            $status = 'ok';
            $isDue = false;

            if ($nextDueKm && $vehicle->mileage >= $nextDueKm) {
                $status = 'overdue';
                $isDue = true;
            } elseif ($nextDueDate && now()->startOfDay()->greaterThanOrEqualTo($nextDueDate)) {
                $status = 'overdue';
                $isDue = true;
            } elseif ($nextDueKm && $vehicle->mileage >= ($nextDueKm - $user->advance_alerts_mileage)) {
                $status = 'upcoming';
            } elseif ($nextDueDate && now()->startOfDay()->greaterThanOrEqualTo(
                $nextDueDate->copy()->subDays($user->advance_alerts_time)
            )) {
                $status = 'upcoming';
            }

            if (! $nextDueKm && ! $nextDueDate) {
                return null;
            }

            return [
                'task_id' => $task->id,
                'task_name' => '🔍 '.$task->name.' (Revisión)',
                'description' => $task->description,
                'frequency_km' => $task->review_frequency_km,
                'frequency_time_months' => $task->review_frequency_time_months,
                'next_due_km' => $nextDueKm,
                'next_due_date' => $nextDueDate?->toDateString(),
                'status' => $status,
                'last_record' => $lastRecord ? [
                    'date' => $lastRecord->date->toDateString(),
                    'mileage' => $lastRecord->mileage,
                ] : null,
            ];
        })->filter()->values();

        return response()->json($reminders->merge($reviewReminders));
    }

    public function assignPlan(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'maintenance_plan_id' => 'required|exists:maintenance_plans,id',
        ]);

        $plan = MaintenancePlan::findOrFail($data['maintenance_plan_id']);

        if (! $plan->is_predefined && $plan->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $vehicle->update(['maintenance_plan_id' => $plan->id]);

        return response()->json($vehicle->load('maintenancePlan.tasks'));
    }

    public function copyPlan(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'source_vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $sourceVehicle = Vehicle::findOrFail($data['source_vehicle_id']);

        if ($sourceVehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $sourcePlan = $sourceVehicle->maintenancePlan;

        if (! $sourcePlan) {
            return response()->json(['error' => 'Source vehicle has no plan'], 400);
        }

        $newPlan = MaintenancePlan::create([
            'user_id' => auth()->id(),
            'name' => $sourcePlan->name.' (copia)',
            'is_predefined' => false,
        ]);

        foreach ($sourcePlan->tasks as $task) {
            MaintenancePlanTask::create([
                'maintenance_plan_id' => $newPlan->id,
                'name' => $task->name,
                'description' => $task->description,
                'frequency_km' => $task->frequency_km,
                'frequency_time_months' => $task->frequency_time_months,
                'review_frequency_km' => $task->review_frequency_km,
                'review_frequency_time_months' => $task->review_frequency_time_months,
                'is_active' => $task->is_active,
            ]);
        }

        $vehicle->update(['maintenance_plan_id' => $newPlan->id]);

        return response()->json($vehicle->load('maintenancePlan.tasks'));
    }

    public function export(Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $vehicle->load('maintenancePlan.tasks', 'maintenanceRecords');

        return response()->json($vehicle);
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'nickname' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:2099',
            'mileage' => 'required|integer|min:0',
            'plate' => 'nullable|string|max:20',
            'maintenance_plan' => 'nullable|array',
            'maintenance_plan.name' => 'required_with:maintenance_plan|string',
            'maintenance_plan.tasks' => 'nullable|array',
            'maintenance_plan.tasks.*.name' => 'required|string',
            'maintenance_plan.tasks.*.frequency_km' => 'nullable|integer',
            'maintenance_plan.tasks.*.frequency_time_months' => 'nullable|integer',
            'maintenance_plan.tasks.*.review_frequency_km' => 'nullable|integer',
            'maintenance_plan.tasks.*.review_frequency_time_months' => 'nullable|integer',
            'maintenance_records' => 'nullable|array',
            'maintenance_records.*.maintenance_plan_task_id' => 'required|exists:maintenance_plan_tasks,id',
            'maintenance_records.*.date' => 'required|date',
            'maintenance_records.*.mileage' => 'required|integer',
            'maintenance_records.*.cost' => 'nullable|numeric',
            'maintenance_records.*.notes' => 'nullable|string',
        ]);

        $vehicleData = collect($data)->only(['nickname', 'brand', 'model', 'year', 'mileage', 'plate'])->toArray();
        $vehicleData['user_id'] = auth()->id();

        $vehicle = Vehicle::create($vehicleData);

        if (isset($data['maintenance_plan'])) {
            $plan = MaintenancePlan::create([
                'user_id' => auth()->id(),
                'name' => $data['maintenance_plan']['name'],
                'is_predefined' => false,
            ]);

            if (isset($data['maintenance_plan']['tasks'])) {
                foreach ($data['maintenance_plan']['tasks'] as $taskData) {
                    MaintenancePlanTask::create([
                        'maintenance_plan_id' => $plan->id,
                        ...$taskData,
                    ]);
                }
            }

            $vehicle->update(['maintenance_plan_id' => $plan->id]);
        }

        if (isset($data['maintenance_records'])) {
            foreach ($data['maintenance_records'] as $recordData) {
                MaintenanceRecord::create([
                    'vehicle_id' => $vehicle->id,
                    ...$recordData,
                ]);
            }
        }

        $vehicle->load('maintenancePlan.tasks', 'maintenanceRecords');

        return response()->json($vehicle, 201);
    }
}
