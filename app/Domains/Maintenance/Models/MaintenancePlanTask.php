<?php

namespace App\Domains\Maintenance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenancePlanTask extends Model
{
    protected $fillable = [
        'maintenance_plan_id',
        'name',
        'description',
        'frequency_km',
        'frequency_time_months',
        'review_frequency_km',
        'review_frequency_time_months',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'frequency_km' => 'integer',
            'frequency_time_months' => 'integer',
            'review_frequency_km' => 'integer',
            'review_frequency_time_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function maintenancePlan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
