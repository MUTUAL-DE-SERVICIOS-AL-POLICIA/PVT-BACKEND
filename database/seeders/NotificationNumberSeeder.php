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
        DB::table('notification_numbers')->truncate();
        DB::statement('alter sequence notification_numbers_id_seq restart with 1');
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
