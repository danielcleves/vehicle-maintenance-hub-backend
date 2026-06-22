<?php

namespace Database\Seeders;

use App\Domains\Maintenance\Models\MaintenancePlan;
use App\Domains\Maintenance\Models\MaintenancePlanTask;
use Illuminate\Database\Seeder;

class PredefinedPlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->createCarPlan();
        $this->createMotorcyclePlan();
        $this->createElectricVehiclePlan();
    }

    private function createCarPlan(): void
    {
        $plan = MaintenancePlan::updateOrCreate(
            ['name' => 'Plan para Auto', 'is_predefined' => true],
            ['user_id' => null]
        );

        $tasks = [
            [
                'name' => 'Cambio de Aceite y Filtro',
                'description' => 'Cambiar aceite del motor y filtro de aceite',
                'frequency_km' => 10000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 5000,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Revisión de Frenos',
                'description' => 'Revisar pastillas, discos y líquido de frenos',
                'frequency_km' => 20000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 10000,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Rotación de Neumáticos',
                'description' => 'Rotar neumáticos para desgaste uniforme',
                'frequency_km' => 10000,
                'frequency_time_months' => null,
                'review_frequency_km' => 5000,
                'review_frequency_time_months' => null,
            ],
            [
                'name' => 'Cambio de Filtro de Aire',
                'description' => 'Reemplazar filtro de aire del motor',
                'frequency_km' => 20000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 10000,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Cambio de Bujías',
                'description' => 'Reemplazar bujías de encendido',
                'frequency_km' => 40000,
                'frequency_time_months' => null,
                'review_frequency_km' => 20000,
                'review_frequency_time_months' => null,
            ],
            [
                'name' => 'Revisión de Suspensión',
                'description' => 'Inspeccionar amortiguadores y componentes de suspensión',
                'frequency_km' => 30000,
                'frequency_time_months' => 24,
                'review_frequency_km' => 15000,
                'review_frequency_time_months' => 12,
            ],
            [
                'name' => 'Cambio de Líquido Refrigerante',
                'description' => 'Reemplazar anticongelante/refrigerante',
                'frequency_km' => 40000,
                'frequency_time_months' => 24,
                'review_frequency_km' => 20000,
                'review_frequency_time_months' => 12,
            ],
            [
                'name' => 'Revisión de Correa de Distribución',
                'description' => 'Inspeccionar y reemplazar correa de distribución',
                'frequency_km' => 60000,
                'frequency_time_months' => 60,
                'review_frequency_km' => 30000,
                'review_frequency_time_months' => 24,
            ],
            [
                'name' => 'Alineación y Balanceo',
                'description' => 'Alinear dirección y balancear neumáticos',
                'frequency_km' => 15000,
                'frequency_time_months' => null,
                'review_frequency_km' => 7500,
                'review_frequency_time_months' => null,
            ],
            [
                'name' => 'Cambio de Filtro de Cabina',
                'description' => 'Reemplazar filtro de aire del habitáculo',
                'frequency_km' => 15000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 7500,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Seguro del Vehículo',
                'description' => 'Pago y renovación del seguro',
                'frequency_km' => null,
                'frequency_time_months' => 12,
            ],
            [
                'name' => 'Impuesto Vehicular / Revisión Técnica',
                'description' => 'Pago de impuesto anual y/o revisión técnica',
                'frequency_km' => null,
                'frequency_time_months' => 12,
            ],
        ];

        foreach ($tasks as $task) {
            MaintenancePlanTask::updateOrCreate(
                [
                    'maintenance_plan_id' => $plan->id,
                    'name' => $task['name'],
                ],
                $task
            );
        }
    }

    private function createMotorcyclePlan(): void
    {
        $plan = MaintenancePlan::updateOrCreate(
            ['name' => 'Plan para Moto', 'is_predefined' => true],
            ['user_id' => null]
        );

        $tasks = [
            [
                'name' => 'Cambio de Aceite y Filtro',
                'description' => 'Cambiar aceite del motor y filtro de aceite',
                'frequency_km' => 5000,
                'frequency_time_months' => 6,
                'review_frequency_km' => 2500,
                'review_frequency_time_months' => 3,
            ],
            [
                'name' => 'Limpieza y Ajuste de Cadena',
                'description' => 'Limpiar, lubricar y ajustar tensión de la cadena de transmisión',
                'frequency_km' => 1000,
                'frequency_time_months' => null,
                'review_frequency_km' => 500,
                'review_frequency_time_months' => null,
            ],
            [
                'name' => 'Revisión de Frenos',
                'description' => 'Revisar pastillas de freno y nivel de líquido',
                'frequency_km' => 10000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 5000,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Cambio de Neumáticos',
                'description' => 'Revisar desgaste y presión de neumáticos',
                'frequency_km' => 15000,
                'frequency_time_months' => null,
                'review_frequency_km' => 5000,
                'review_frequency_time_months' => null,
            ],
            [
                'name' => 'Revisión de Bujía',
                'description' => 'Inspeccionar y reemplazar bujía de encendido',
                'frequency_km' => 15000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 7500,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Cambio de Filtro de Aire',
                'description' => 'Reemplazar filtro de aire del motor',
                'frequency_km' => 12000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 6000,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Revisión de Suspensión',
                'description' => 'Inspeccionar horquilla delantera y amortiguadores traseros',
                'frequency_km' => 20000,
                'frequency_time_months' => 24,
                'review_frequency_km' => 10000,
                'review_frequency_time_months' => 12,
            ],
            [
                'name' => 'Cambio de Líquido de Frenos',
                'description' => 'Reemplazar líquido de frenos',
                'frequency_km' => null,
                'frequency_time_months' => 24,
                'review_frequency_km' => null,
                'review_frequency_time_months' => 12,
            ],
            [
                'name' => 'Ajuste de Válvulas',
                'description' => 'Revisar y ajustar holgura de válvulas',
                'frequency_km' => 20000,
                'frequency_time_months' => null,
                'review_frequency_km' => 10000,
                'review_frequency_time_months' => null,
            ],
            [
                'name' => 'Revisión de Batería',
                'description' => 'Verificar estado y bornes de la batería',
                'frequency_km' => null,
                'frequency_time_months' => 12,
                'review_frequency_km' => null,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Seguro de la Moto',
                'description' => 'Pago y renovación del seguro',
                'frequency_km' => null,
                'frequency_time_months' => 12,
            ],
            [
                'name' => 'Impuesto / Revisión Técnica',
                'description' => 'Pago de impuesto anual y/o revisión técnica',
                'frequency_km' => null,
                'frequency_time_months' => 12,
            ],
        ];

        foreach ($tasks as $task) {
            MaintenancePlanTask::updateOrCreate(
                [
                    'maintenance_plan_id' => $plan->id,
                    'name' => $task['name'],
                ],
                $task
            );
        }
    }

    private function createElectricVehiclePlan(): void
    {
        $plan = MaintenancePlan::updateOrCreate(
            ['name' => 'Plan para Vehículo Eléctrico', 'is_predefined' => true],
            ['user_id' => null]
        );

        $tasks = [
            [
                'name' => 'Revisión de Batería de Alto Voltaje',
                'description' => 'Verificar estado de carga, celdas y sistema de refrigeración de la batería',
                'frequency_km' => 20000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 10000,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Rotación de Neumáticos',
                'description' => 'Rotar neumáticos para desgaste uniforme',
                'frequency_km' => 10000,
                'frequency_time_months' => null,
                'review_frequency_km' => 5000,
                'review_frequency_time_months' => null,
            ],
            [
                'name' => 'Revisión de Frenos Regenerativos',
                'description' => 'Inspeccionar sistema de frenos regenerativos y pastillas',
                'frequency_km' => 20000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 10000,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Revisión de Líquido de Frenos',
                'description' => 'Verificar nivel y estado del líquido de frenos',
                'frequency_km' => null,
                'frequency_time_months' => 24,
                'review_frequency_km' => null,
                'review_frequency_time_months' => 12,
            ],
            [
                'name' => 'Cambio de Filtro de Aire de Cabina',
                'description' => 'Reemplazar filtro de aire del habitáculo',
                'frequency_km' => 20000,
                'frequency_time_months' => 12,
                'review_frequency_km' => 10000,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Revisión de Suspensión',
                'description' => 'Inspeccionar amortiguadores y componentes de suspensión',
                'frequency_km' => 30000,
                'frequency_time_months' => 24,
                'review_frequency_km' => 15000,
                'review_frequency_time_months' => 12,
            ],
            [
                'name' => 'Actualización de Software',
                'description' => 'Verificar y actualizar software del vehículo',
                'frequency_km' => null,
                'frequency_time_months' => 12,
                'review_frequency_km' => null,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Revisión del Inversor/Motor Eléctrico',
                'description' => 'Inspeccionar motor eléctrico e inversor',
                'frequency_km' => 40000,
                'frequency_time_months' => 24,
                'review_frequency_km' => 20000,
                'review_frequency_time_months' => 12,
            ],
            [
                'name' => 'Revisión de Sistema de Carga',
                'description' => 'Verificar cable de carga, conector y puerto de carga',
                'frequency_km' => null,
                'frequency_time_months' => 12,
                'review_frequency_km' => null,
                'review_frequency_time_months' => 6,
            ],
            [
                'name' => 'Revisión de Líquido Refrigerante del BMS',
                'description' => 'Verificar nivel de refrigerante del sistema de gestión de batería',
                'frequency_km' => 40000,
                'frequency_time_months' => 24,
                'review_frequency_km' => 20000,
                'review_frequency_time_months' => 12,
            ],
            [
                'name' => 'Seguro del Vehículo',
                'description' => 'Pago y renovación del seguro',
                'frequency_km' => null,
                'frequency_time_months' => 12,
            ],
            [
                'name' => 'Impuesto Vehicular / Revisión Técnica',
                'description' => 'Pago de impuesto anual y/o revisión técnica',
                'frequency_km' => null,
                'frequency_time_months' => 12,
            ],
        ];

        foreach ($tasks as $task) {
            MaintenancePlanTask::updateOrCreate(
                [
                    'maintenance_plan_id' => $plan->id,
                    'name' => $task['name'],
                ],
                $task
            );
        }
    }
}
