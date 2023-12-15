<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Notifications\Notifiable;

class AllowedMacDevices extends Model
{
    use LaratrustUserTrait;
    use HasApiTokens, HasFactory, Notifiable;
    public $timestamps = true;
    public $guarded = ['id'];
    public $fillable = ['device_name', 'password', 'api_token', 'mac_address', 'is_enable', 'Description'];

    public function getMacAddressHashAttribute()
    {
        return bcrypt($this->address);
    }
}
