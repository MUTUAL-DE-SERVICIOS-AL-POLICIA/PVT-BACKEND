<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification\NotificationCarrier;
use App\Models\Notification\NotificationNumber;
use App\Models\Notification\NotificationSend;
use App\Models\Affiliate\Affiliate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessRegisterNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $shippments;
    public $user_id;
    public $transmitter_id;
    public $type;
    public $timeout = 86400;

    public function __construct($shippments, $user_id, $transmitter_id, $type)
    {
        $this->shippments = $shippments;
        $this->user_id = $user_id;
        $this->transmitter_id = $transmitter_id;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $issuer_number = NotificationNumber::find($this->transmitter_id)->number;
        $obj = new Affiliate();
        $alias = $obj->getMorphClass();
        foreach($this->shippments as $shipping) {
            DB::transaction(function () use ($shipping, $issuer_number, $alias) {
                $notification_send = new NotificationSend();
                $notification_send->create([
                    'user_id' => $this->user_id,
                    'carrier_id' => NotificationCarrier::whereName('SMS')->first()->id,
                    'sender_number' => NotificationNumber::whereNumber($issuer_number)->first()->id,
                    'sendable_type' => $alias,
                    'sendable_id' => $shipping['id'],
                    'send_date' => Carbon::now(),
                    'delivered' => true,
                    'message' => json_encode(['data' => ['text' => $shipping['message']]]),
                    'subject' => null,
                    'receiver_number' => $shipping['sms_num'],
                    'notification_type_id' => $this->type
                ]);
            });
        }
    }
}
