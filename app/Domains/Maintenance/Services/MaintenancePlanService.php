<?php

namespace App\Domains\Maintenance\Services;

use App\Domains\Maintenance\Models\MaintenancePlan;
use App\Domains\Maintenance\Models\MaintenancePlanTask;

class MaintenancePlanService
{
    public function createWithTasks(array $data): MaintenancePlan
    {
        $plan = MaintenancePlan::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'is_predefined' => false,
        ]);

        foreach ($data['tasks'] as $taskData) {
            MaintenancePlanTask::create([
                'maintenance_plan_id' => $plan->id,
                'name' => $taskData['name'],
                'description' => $taskData['description'] ?? null,
                'frequency_km' => $taskData['frequency_km'] ?? null,
                'frequency_time_months' => $taskData['frequency_time_months'] ?? null,
                'review_frequency_km' => $taskData['review_frequency_km'] ?? null,
                'review_frequency_time_months' => $taskData['review_frequency_time_months'] ?? null,
                'is_active' => $taskData['is_active'] ?? true,
            ]);
        }

        return $plan->load('tasks');
    }

    public function syncTasks(int $planId, array $tasks): MaintenancePlan
    {
        $plan = MaintenancePlan::with('tasks')->findOrFail($planId);

        $existingIds = $plan->tasks->pluck('id')->toArray();
        $incomingIds = [];

        foreach ($tasks as $taskData) {
            if (isset($taskData['id']) && in_array($taskData['id'], $existingIds)) {
                $task = MaintenancePlanTask::find($taskData['id']);
                $task->update($taskData);
                $incomingIds[] = $taskData['id'];
            } else {
                $task = MaintenancePlanTask::create([
                    'maintenance_plan_id' => $plan->id,
                    'name' => $taskData['name'],
                    'description' => $taskData['description'] ?? null,
                    'frequency_km' => $taskData['frequency_km'] ?? null,
                    'frequency_time_months' => $taskData['frequency_time_months'] ?? null,
                    'review_frequency_km' => $taskData['review_frequency_km'] ?? null,
                    'review_frequency_time_months' => $taskData['review_frequency_time_months'] ?? null,
                    'is_active' => $taskData['is_active'] ?? true,
                ]);
                $incomingIds[] = $task->id;
            }
        }

        $toDelete = array_diff($existingIds, $incomingIds);
        if (! empty($toDelete)) {
            MaintenancePlanTask::whereIn('id', $toDelete)->delete();
        }

        return $plan->fresh()->load('tasks');
    }

    public function convertPredefined(int $planId): MaintenancePlan
    {
        $plan = MaintenancePlan::with('tasks')->findOrFail($planId);

        $newPlan = MaintenancePlan::create([
            'user_id' => auth()->id(),
            'name' => $plan->name,
            'is_predefined' => false,
        ]);

        foreach ($plan->tasks as $task) {
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

        return $newPlan->load('tasks');
    }

    public function getAllPlans(): array
    {
        $predefined = MaintenancePlan::where('is_predefined', true)
            ->with('tasks')
            ->get();

        $custom = MaintenancePlan::where('user_id', auth()->id())
            ->with('tasks')
            ->get();

        return [
            'predefined' => $predefined,
            'custom' => $custom,
        ];
    }
}
