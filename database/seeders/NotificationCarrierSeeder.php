<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Notification\NotificationCarrier;
use Illuminate\Support\Facades\DB;

class NotificationCarrierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $notification_carriers = [
            [
                'module_id' => 2,
                'image' => env('NOTIFICATION_IMAGE', null),
                'name' => 'Notifications'
            ],
            [
                'module_id' => 6,
                'image' => null,
                'name' => 'SMS'
            ]
        ];
        foreach ($notification_carriers as $notification_carrier) {
            NotificationCarrier::firstOrCreate($notification_carrier);
        }

    }
}
