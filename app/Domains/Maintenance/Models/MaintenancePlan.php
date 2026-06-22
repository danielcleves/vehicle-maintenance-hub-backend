<?php

namespace App\Domains\Maintenance\Models;

use App\Domains\Users\Models\User;
use App\Domains\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenancePlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'is_predefined',
    ];

    protected function casts(): array
    {
        return [
            'is_predefined' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MaintenancePlanTask::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
