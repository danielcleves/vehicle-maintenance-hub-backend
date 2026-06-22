<?php

namespace App\Domains\Maintenance\Controllers;

use App\Domains\Maintenance\Models\MaintenancePlan;
use App\Domains\Maintenance\Models\MaintenancePlanTask;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaintenancePlanController extends Controller
{
    public function index()
    {
        $predefined = MaintenancePlan::where('is_predefined', true)
            ->with('tasks')
            ->get();

        $custom = MaintenancePlan::where('user_id', auth()->id())
            ->with('tasks')
            ->get();

        return response()->json([
            'predefined' => $predefined,
            'custom' => $custom,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'tasks' => 'required|array|min:1',
            'tasks.*.name' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.frequency_km' => 'nullable|integer|min:0',
            'tasks.*.frequency_time_months' => 'nullable|integer|min:0',
            'tasks.*.review_frequency_km' => 'nullable|integer|min:0',
            'tasks.*.review_frequency_time_months' => 'nullable|integer|min:0',
            'tasks.*.is_active' => 'nullable|boolean',
        ]);

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

        return response()->json($plan->load('tasks'), 201);
    }

    public function show($id)
    {
        $plan = MaintenancePlan::with('tasks')->findOrFail($id);

        if (! $plan->is_predefined && $plan->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json($plan);
    }

    public function update(Request $request, $id)
    {
        $plan = MaintenancePlan::findOrFail($id);

        if ($plan->is_predefined || $plan->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $plan->update($data);

        return response()->json($plan->load('tasks'));
    }

    public function destroy($id)
    {
        $plan = MaintenancePlan::findOrFail($id);

        if ($plan->is_predefined || $plan->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $plan->delete();

        return response()->json(['message' => 'Plan deleted']);
    }

    public function syncTasks(Request $request, $id)
    {
        $plan = MaintenancePlan::with('tasks')->findOrFail($id);

        if ($plan->is_predefined || $plan->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'nullable|integer|exists:maintenance_plan_tasks,id',
            'tasks.*.name' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.frequency_km' => 'nullable|integer|min:0',
            'tasks.*.frequency_time_months' => 'nullable|integer|min:0',
            'tasks.*.review_frequency_km' => 'nullable|integer|min:0',
            'tasks.*.review_frequency_time_months' => 'nullable|integer|min:0',
            'tasks.*.is_active' => 'nullable|boolean',
        ]);

        $existingIds = $plan->tasks->pluck('id')->toArray();
        $incomingIds = [];

        foreach ($data['tasks'] as $taskData) {
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

        return response()->json($plan->fresh()->load('tasks'));
    }

    public function convert(Request $request, $id)
    {
        $plan = MaintenancePlan::with('tasks')->findOrFail($id);

        if (! $plan->is_predefined) {
            return response()->json(['error' => 'Plan is not predefined'], 400);
        }

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

        return response()->json($newPlan->load('tasks'), 201);
    }
}
