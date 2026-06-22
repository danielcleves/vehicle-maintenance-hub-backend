<?php

namespace App\Domains\Vehicles\Controllers;

use App\Domains\Maintenance\Models\MaintenancePlan;
use App\Domains\Maintenance\Services\MaintenanceComputationService;
use App\Domains\Vehicles\Models\Vehicle;
use App\Domains\Vehicles\Services\VehicleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService,
        private readonly MaintenanceComputationService $computationService,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $vehicles = $user->vehicles()->with('maintenancePlan.tasks', 'maintenanceRecords')->get();

        $vehicles = $vehicles->map(function ($vehicle) use ($user) {
            $vehicleArray = $vehicle->toArray();

            if ($vehicle->maintenancePlan) {
                [$overdue, $upcoming] = $this->computationService->computeAlertCounts(
                    $vehicle->maintenancePlan,
                    $vehicle->maintenanceRecords,
                    $vehicle,
                    $user,
                );

                $vehicleArray['overdue_count'] = $overdue;
                $vehicleArray['upcoming_count'] = $upcoming;
            } else {
                $vehicleArray['overdue_count'] = 0;
                $vehicleArray['upcoming_count'] = 0;
            }

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

                $info = $this->computationService->computeTaskInfo($task, $records, $vehicle, $user);

                $taskArray['next_maintenance'] = $info['next_maintenance'];
                $taskArray['next_review'] = $info['next_review'];

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

        $records = $vehicle->maintenanceRecords;
        $user = auth()->user();

        return response()->json(
            $this->computationService->computeReminders($plan, $records, $vehicle, $user)
        );
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

        return response()->json($this->vehicleService->assignPlan($vehicle, $plan->id));
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

        if (! $sourceVehicle->maintenancePlan) {
            return response()->json(['error' => 'Source vehicle has no plan'], 400);
        }

        return response()->json(
            $this->vehicleService->copyPlanFromVehicle($vehicle, $sourceVehicle->id)
        );
    }

    public function export(Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json($this->vehicleService->exportVehicle($vehicle));
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

        return response()->json($this->vehicleService->importWithRelations($data), 201);
    }
}
