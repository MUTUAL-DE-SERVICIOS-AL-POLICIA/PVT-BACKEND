<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationNumber extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function send(){
        return $this->hasMany(NotificationSend::class, 'number_id');
    }
}
