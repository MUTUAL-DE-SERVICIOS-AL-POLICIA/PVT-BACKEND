<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationNumber extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function send(){
        return $this->hasOne(NotificationSend::class);
    }

    public function sends(){
        return $this->morphMany(NotificationSend::class, 'sendable');
    }
}
