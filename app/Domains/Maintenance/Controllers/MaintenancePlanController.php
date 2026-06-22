<?php

namespace App\Domains\Maintenance\Controllers;

use App\Domains\Maintenance\Models\MaintenancePlan;
use App\Domains\Maintenance\Services\MaintenancePlanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaintenancePlanController extends Controller
{
    public function __construct(
        private readonly MaintenancePlanService $planService,
    ) {}

    public function index()
    {
        return response()->json($this->planService->getAllPlans());
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

        return response()->json($this->planService->createWithTasks($data), 201);
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

        return response()->json($this->planService->syncTasks((int) $plan->id, $data['tasks']));
    }

    public function convert(Request $request, $id)
    {
        $plan = MaintenancePlan::with('tasks')->findOrFail($id);

        if (! $plan->is_predefined) {
            return response()->json(['error' => 'Plan is not predefined'], 400);
        }

        return response()->json($this->planService->convertPredefined((int) $plan->id), 201);
    }
}
