<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Notification\NotificationType;

class NotificationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $notification_types = [
            [
                'type' => 'Recepción de requisitos',
            ],
            [
                'type' => 'Pago de complemento económico',
            ],
            [
                'type' => 'Observación de complemento económico',
            ],
            [
                'type' => 'Desembolso de préstamo',
            ],
            [
                'type' => 'Contrato de préstamo',
            ],
            [
                'type' => 'Envío de credenciales de la oficina virtual'
            ],
            [
                'type' => 'Envío mediante archivo'
            ]
        ];
        foreach ($notification_types as $notification_type) {
            NotificationType::firstOrCreate($notification_type);
        }
    }
}
