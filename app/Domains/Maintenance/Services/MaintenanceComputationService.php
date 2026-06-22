<?php

namespace App\Domains\Maintenance\Services;

use App\Domains\Maintenance\Models\MaintenancePlan;
use App\Domains\Maintenance\Models\MaintenancePlanTask;
use App\Domains\Maintenance\Models\MaintenanceRecord;
use App\Domains\Users\Models\User;
use App\Domains\Vehicles\Models\Vehicle;
use Illuminate\Support\Collection;

class MaintenanceComputationService
{
    private const TYPE_MAINTENANCE = 'maintenance';

    private const TYPE_REVIEW = 'review';

    public function computeNextDue(
        MaintenancePlanTask $task,
        ?MaintenanceRecord $lastRecord,
        Vehicle $vehicle,
        User $user,
        string $type = self::TYPE_MAINTENANCE
    ): array {
        $frequencyKm = $type === self::TYPE_REVIEW ? $task->review_frequency_km : $task->frequency_km;
        $frequencyTimeMonths = $type === self::TYPE_REVIEW ? $task->review_frequency_time_months : $task->frequency_time_months;

        $lastMileage = $lastRecord?->mileage ?? $vehicle->mileage;
        $lastDate = $lastRecord?->date ?? $vehicle->created_at;

        $nextDueKm = $frequencyKm ? $lastMileage + $frequencyKm : null;
        $nextDueDate = $frequencyTimeMonths ? $lastDate->copy()->addMonths($frequencyTimeMonths) : null;

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

        return [
            'next_due_km' => $nextDueKm,
            'next_due_date' => $nextDueDate?->toDateString(),
            'remaining_km' => $remainingKm,
            'remaining_days' => $remainingDays,
            'status' => $status,
        ];
    }

    public function computeAlertCounts(MaintenancePlan $plan, Collection $records, Vehicle $vehicle, User $user): array
    {
        $overdue = 0;
        $upcoming = 0;

        foreach ($plan->tasks as $task) {
            $lastRecord = $records
                ->where('maintenance_plan_task_id', $task->id)
                ->sortByDesc('date')
                ->first();

            $maintenanceInfo = $this->computeNextDue($task, $lastRecord, $vehicle, $user, self::TYPE_MAINTENANCE);
            if ($maintenanceInfo['status'] === 'overdue') {
                $overdue++;
            } elseif ($maintenanceInfo['status'] === 'upcoming') {
                $upcoming++;
            }

            $reviewInfo = $this->computeNextDue($task, $lastRecord, $vehicle, $user, self::TYPE_REVIEW);
            if ($reviewInfo['status'] === 'overdue') {
                $overdue++;
            } elseif ($reviewInfo['status'] === 'upcoming') {
                $upcoming++;
            }
        }

        return [$overdue, $upcoming];
    }

    public function computeTaskInfo(MaintenancePlanTask $task, Collection $records, Vehicle $vehicle, User $user): array
    {
        $lastRecord = $records
            ->where('maintenance_plan_task_id', $task->id)
            ->sortByDesc('date')
            ->first();

        return [
            'next_maintenance' => $this->computeNextDue($task, $lastRecord, $vehicle, $user, self::TYPE_MAINTENANCE),
            'next_review' => $this->computeNextDue($task, $lastRecord, $vehicle, $user, self::TYPE_REVIEW),
        ];
    }

    public function computeReminders(MaintenancePlan $plan, Collection $records, Vehicle $vehicle, User $user): array
    {
        $tasks = $plan->tasks()->where('is_active', true)->get();

        $reminders = $tasks->map(function ($task) use ($records, $vehicle, $user) {
            $lastRecord = $records
                ->where('maintenance_plan_task_id', $task->id)
                ->sortByDesc('date')
                ->first();

            $info = $this->computeNextDue($task, $lastRecord, $vehicle, $user, self::TYPE_MAINTENANCE);

            return [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'description' => $task->description,
                'frequency_km' => $task->frequency_km,
                'frequency_time_months' => $task->frequency_time_months,
                'next_due_km' => $info['next_due_km'],
                'next_due_date' => $info['next_due_date'],
                'status' => $info['status'],
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

            $info = $this->computeNextDue($task, $lastRecord, $vehicle, $user, self::TYPE_REVIEW);

            if (! $info['next_due_km'] && ! $info['next_due_date']) {
                return null;
            }

            return [
                'task_id' => $task->id,
                'task_name' => '🔍 '.$task->name.' (Revisión)',
                'description' => $task->description,
                'frequency_km' => $task->review_frequency_km,
                'frequency_time_months' => $task->review_frequency_time_months,
                'next_due_km' => $info['next_due_km'],
                'next_due_date' => $info['next_due_date'],
                'status' => $info['status'],
                'last_record' => $lastRecord ? [
                    'date' => $lastRecord->date->toDateString(),
                    'mileage' => $lastRecord->mileage,
                ] : null,
            ];
        })->filter()->values();

        return $reminders->merge($reviewReminders)->toArray();
    }
}
