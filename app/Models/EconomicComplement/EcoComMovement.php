<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcoComMovement extends Model
{
    use HasFactory;
    use SoftDeletes;
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
