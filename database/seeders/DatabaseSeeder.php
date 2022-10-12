<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(ActionSeeder::class);
        $this->call(ContributionPermissionSeedeer::class);
        $this->call(ContributionRolePermissionSeeder::class);
        $this->call(ContributionStateSeeder::class);
        $this->call(NotificationCarrierSeeder::class);
    }
}
