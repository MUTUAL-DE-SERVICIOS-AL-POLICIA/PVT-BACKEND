<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcoComDirectPayment extends Model
{
    use HasFactory;
    use SoftDeletes;
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'amount',
        'voucher',
        'payment_date'
    ];
    public function comments()
    {
        return $this->morphMany(EcoComMovement::class, 'EcoComMovement');
    }
}
