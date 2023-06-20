<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\Util;

class ProcessNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $shippable;
    public $user_id;
    public $timeout = 86400;
    public $transmitter;
    public $morph;

    public function __construct($shippable, $user_id, $transmitter, $morph)
    {
        $this->shippable = $shippable;
        $this->user_id = $user_id;
        $this->transmitter = $transmitter;
        $this->morph = $morph;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Util::delegate_shipping($this->shippable, $this->user_id, $this->transmitter, $this->morph);
    }
}
