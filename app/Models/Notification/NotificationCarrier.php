<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Module;

class NotificationCarrier extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function module(){
        return $this->belongsTo(Module::class);
    }

    public function send() {
        return $this->hasMany(NotificationSend::class, 'carrier_id');
    }
}
