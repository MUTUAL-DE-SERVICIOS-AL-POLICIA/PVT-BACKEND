<?php

namespace App\Models\EconomicComplement;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoComProcedure extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'year',
        'semester',
        'normal_start_date',
        'normal_end_date',
        'lagging_start_date',
        'lagging_end_date',
        'additional_start_date',
        'additional_end_date',
        'indicator',
        'rent_month',
        'sequence'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
