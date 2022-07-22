<?php

namespace App\Models\QuotaAidMortuary;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotaAidRecord extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'quota_aid_id',
        'message'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function quota_aid()
    {
        return $this->belongsTo(QuotaAidMortuary::class);
    }
}
