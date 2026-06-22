<?php

namespace App\Domains\Vehicles\Services;

use App\Domains\Maintenance\Models\MaintenancePlan;
use App\Domains\Maintenance\Models\MaintenancePlanTask;
use App\Domains\Maintenance\Models\MaintenanceRecord;
use App\Domains\Vehicles\Models\Vehicle;

class VehicleService
{
    public function assignPlan(Vehicle $vehicle, int $planId): Vehicle
    {
        $vehicle->update(['maintenance_plan_id' => $planId]);

        return $vehicle->load('maintenancePlan.tasks');
    }

    public function copyPlanFromVehicle(Vehicle $vehicle, int $sourceVehicleId): Vehicle
    {
        $sourceVehicle = Vehicle::findOrFail($sourceVehicleId);
        $sourcePlan = $sourceVehicle->maintenancePlan;

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

        return $vehicle->load('maintenancePlan.tasks');
    }

    public function importWithRelations(array $data): Vehicle
    {
        $vehicleData = collect($data)->only([
            'nickname', 'brand', 'model', 'year', 'mileage', 'plate',
        ])->toArray();
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

        return $vehicle;
    }

    public function exportVehicle(Vehicle $vehicle): Vehicle
    {
        $vehicle->load('maintenancePlan.tasks', 'maintenanceRecords');

        return $vehicle;
    }
}
