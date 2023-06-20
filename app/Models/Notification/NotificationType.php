<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Notification\NotificationType;

class NotificationType extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function sends() {
        return $this->hasMany(NotificationSend::class);
    }
}
