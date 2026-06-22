<?php

namespace Database\Seeders;

use App\Domains\Users\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'SuperAdmin',
                'email' => 'superadmin@example.com',
                'password' => '$2y$12$5seIJTszouEPTfv55dfPdOgIn/v3FV805XsJOt.tDVmQq6JMi58qK',
                'advance_alerts_time' => 30,
                'advance_alerts_mileage' => 500,
            ]
        );

        $this->call([
            PredefinedPlanSeeder::class,
        ]);
    }
}
