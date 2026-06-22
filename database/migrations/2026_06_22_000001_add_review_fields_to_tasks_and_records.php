<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_plan_tasks', function (Blueprint $table) {
            $table->integer('review_frequency_km')->nullable()->after('frequency_time_months');
            $table->integer('review_frequency_time_months')->nullable()->after('review_frequency_km');
        });

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->boolean('is_review_only')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_plan_tasks', function (Blueprint $table) {
            $table->dropColumn(['review_frequency_km', 'review_frequency_time_months']);
        });

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn('is_review_only');
        });
    }
};
