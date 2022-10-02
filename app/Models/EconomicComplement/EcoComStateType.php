<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoComStateType extends Model
{
    use HasFactory;

    public function eco_com_state() {
        return $this->hasMany(EcoComState::class);
    }
}
