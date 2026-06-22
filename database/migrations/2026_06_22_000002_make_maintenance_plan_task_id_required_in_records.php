<?php

use App\Domains\Maintenance\Models\MaintenanceRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        MaintenanceRecord::whereNull('maintenance_plan_task_id')->delete();

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropForeign(['maintenance_plan_task_id']);
            $table->unsignedBigInteger('maintenance_plan_task_id')->nullable(false)->change();
            $table->foreign('maintenance_plan_task_id')->references('id')->on('maintenance_plan_tasks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropForeign(['maintenance_plan_task_id']);
            $table->unsignedBigInteger('maintenance_plan_task_id')->nullable()->change();
            $table->foreign('maintenance_plan_task_id')->references('id')->on('maintenance_plan_tasks')->nullOnDelete();
        });
    }
};
