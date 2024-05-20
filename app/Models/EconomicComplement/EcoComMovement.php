<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoComMovement extends Model
{
    use HasFactory;
    protected $fillable = [
        'affiliate_id',
        'movement_id',
        'movement_type',
        'amount',
        'balance',
    ];
    public function morphable()
    {
        return $this->morphTo('morphable');
    }
}
