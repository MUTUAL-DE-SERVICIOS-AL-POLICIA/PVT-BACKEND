<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoComDirectPayment extends Model
{
    use HasFactory;
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'amount',
        'voucher'
    ];
    public function comments()
    {
        return $this->morphMany(EcoComMovement::class, 'EcoComMovement');
    }
}
