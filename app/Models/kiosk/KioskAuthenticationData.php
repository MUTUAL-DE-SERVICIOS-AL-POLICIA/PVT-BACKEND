<?php

namespace App\Models\kiosk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KioskAuthenticationData extends Model
{
    use HasFactory;
    protected $connection = 'db_aux';
    protected $table = 'kiosk_authentication_data';

    protected $fillable = [
        'identity_card',
        'affiliate_id',
        'left_text',
        'middle_text',
        'right_text',
        'ocr_state',
        'facial_recognition'
    ];
}
