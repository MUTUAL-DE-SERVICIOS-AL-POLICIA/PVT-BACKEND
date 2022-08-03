<?php

namespace App\Models\Affiliate;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseWage extends Model
{
    use HasFactory;
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'degree_id',
        'month_year',
        'amount'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function degree()
    {
        return $this->belongsTo(Degree::class);
    }
}
