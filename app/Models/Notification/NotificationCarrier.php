<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Module;

class NotificationCarrier extends Model
{
    use HasFactory;

    public function module(){
        return $this->belongsTo(Module::class);
    }
}
