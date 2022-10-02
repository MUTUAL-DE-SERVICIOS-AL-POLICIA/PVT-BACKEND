<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoComState extends Model
{
    use HasFactory;

    public function economic_complement() {
        return $this->hasMany(EconomicComplement::class);
    }

    public function eco_com_state_type(){
        return $this->belongsTo(EcoComStateType::class);
    }
}
