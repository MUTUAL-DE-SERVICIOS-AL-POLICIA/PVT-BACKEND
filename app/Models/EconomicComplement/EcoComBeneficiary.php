<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoComBeneficiary extends Model
{
    use HasFactory;

    protected $table = 'eco_com_applicants';

    public function economic_complement() {
        return $this->belongsTo(EconomicComplement::class);
    }

}
