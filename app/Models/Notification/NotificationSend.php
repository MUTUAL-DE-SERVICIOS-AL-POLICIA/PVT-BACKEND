<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Notification\NotificationNumber;
use App\Models\Notification\NotificationCarrier;
use App\Models\Admin\User;

class NotificationSend extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function number(){
        return $this->belongsTo(NotificationNumber::class);
    }

    public function carrier(){
        return $this->belongsTo(NotificationCarrier::class);
    }
    
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function sendable(){
        return $this->morphTo();
    }
}