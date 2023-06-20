<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Notification\NotificationNumber;
use Illuminate\Support\Facades\DB;

class NotificationNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $notification_numbers = [
            [
                'number' => 71568925,
                'currier' => 'Entel',
                'state_active' => true
            ],
        ];
        foreach ($notification_numbers as $notification_number) {
            NotificationNumber::firstOrCreate($notification_number);
        }
    }
}
