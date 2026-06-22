<?php

namespace App\Domains\Maintenance\Models;

use App\Domains\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    protected $fillable = [
        'vehicle_id',
        'maintenance_plan_task_id',
        'task_name',
        'date',
        'mileage',
        'cost',
        'notes',
        'is_review_only',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mileage' => 'integer',
            'cost' => 'decimal:2',
            'is_review_only' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function maintenancePlanTask(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlanTask::class);
    }
}
