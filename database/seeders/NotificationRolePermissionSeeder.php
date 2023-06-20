<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Admin\Permission;
use \App\Models\Admin\Action;
use \App\Models\Admin\Module;
use \App\Models\Admin\Role;

class NotificationRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::whereDisplayName('Aplicacion Movil')->first();
        $permissions = [
            [
                'action_id' => Action::where('name', 'download')->first()->id,
                'name' => 'download-report-notification',
                'display_name' => 'Descargar reporte de notificaciones'
            ]
        ];
        foreach($permissions as $permission) {
            $permission_new  = Permission::firstOrCreate($permission);
            $permission_id = Permission::whereName($permission_new['name'])->first()->id;
            $role->permissions()->attach([$permission_id]);
        }
    }
}
