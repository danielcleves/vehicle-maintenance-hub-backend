<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_distance_unit', 2)->default('km');
            $table->integer('advance_alerts_time')->default(30);
            $table->integer('advance_alerts_mileage')->default(500);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferred_distance_unit', 'advance_alerts_time', 'advance_alerts_mileage']);
        });
    }
};
