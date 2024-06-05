<?php

namespace Database\Seeders;

use App\Models\Admin\Action;
use App\Models\Admin\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EcoComDevolutionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $permissions = [
            [
                'action_id' => Action::where('name', 'create')->first()->id,
                'name' => 'create-due-economic-complement-movement',
                'display_name' => 'Crear deuda de pago en demasía'
            ], [
                'action_id' => Action::where('name', 'create')->first()->id,
                'name' => 'create-direct-payment-economic-complement-movement',
                'display_name' => 'Crear pago directo de pago en demasía'
            ], [
                'action_id' => Action::where('name', 'read')->first()->id,
                'name' => 'read-economic-complement-movement',
                'display_name' => 'Leer devoluciones de pagos en demasía'
            ], [
                'action_id' => Action::where('name', 'delete')->first()->id,
                'name' => 'delete-economic-complement-movement',
                'display_name' => 'Eliminar movimiento de pago en demasía'
            ]
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
