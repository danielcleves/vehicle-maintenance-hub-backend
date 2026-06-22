<?php

namespace App\Domains\Maintenance\Services;

use App\Domains\Maintenance\Models\MaintenancePlanTask;
use App\Domains\Maintenance\Models\MaintenanceRecord;
use App\Domains\Vehicles\Models\Vehicle;

class MaintenanceRecordService
{
    public function store(Vehicle $vehicle, array $data): MaintenanceRecord
    {
        $task = MaintenancePlanTask::findOrFail($data['maintenance_plan_task_id']);
        $data['task_name'] = $task->name;
        $data['vehicle_id'] = $vehicle->id;

        return MaintenanceRecord::create($data)->load('maintenancePlanTask');
    }

    public function update(MaintenanceRecord $record, array $data): MaintenanceRecord
    {
        if (isset($data['maintenance_plan_task_id'])) {
            $task = MaintenancePlanTask::findOrFail($data['maintenance_plan_task_id']);
            $data['task_name'] = $task->name;
        }

        $record->update($data);

        return $record->fresh()->load('maintenancePlanTask');
    }
}
