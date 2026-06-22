<?php

namespace App\Domains\Maintenance\Controllers;

use App\Domains\Maintenance\Models\MaintenancePlanTask;
use App\Domains\Maintenance\Models\MaintenanceRecord;
use App\Domains\Vehicles\Models\Vehicle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaintenanceRecordController extends Controller
{
    public function index(Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $records = $vehicle->maintenanceRecords()
            ->with('maintenancePlanTask')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($records);
    }

    public function store(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'maintenance_plan_task_id' => 'required|exists:maintenance_plan_tasks,id',
            'date' => 'required|date',
            'mileage' => 'required|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_review_only' => 'nullable|boolean',
        ]);

        $task = MaintenancePlanTask::findOrFail($data['maintenance_plan_task_id']);
        $data['task_name'] = $task->name;
        $data['vehicle_id'] = $vehicle->id;

        $record = MaintenanceRecord::create($data);

        return response()->json($record->load('maintenancePlanTask'), 201);
    }

    public function show(Vehicle $vehicle, MaintenanceRecord $record)
    {
        if ($vehicle->user_id !== auth()->id() || $record->vehicle_id !== $vehicle->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json($record->load('maintenancePlanTask'));
    }

    public function update(Request $request, Vehicle $vehicle, MaintenanceRecord $record)
    {
        if ($vehicle->user_id !== auth()->id() || $record->vehicle_id !== $vehicle->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'maintenance_plan_task_id' => 'required|exists:maintenance_plan_tasks,id',
            'date' => 'sometimes|date',
            'mileage' => 'sometimes|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_review_only' => 'nullable|boolean',
        ]);

        if (isset($data['maintenance_plan_task_id'])) {
            $task = MaintenancePlanTask::findOrFail($data['maintenance_plan_task_id']);
            $data['task_name'] = $task->name;
        }

        $record->update($data);

        return response()->json($record->load('maintenancePlanTask'));
    }

    public function destroy(Vehicle $vehicle, MaintenanceRecord $record)
    {
        if ($vehicle->user_id !== auth()->id() || $record->vehicle_id !== $vehicle->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $record->delete();

        return response()->json(['message' => 'Record deleted']);
    }
}
