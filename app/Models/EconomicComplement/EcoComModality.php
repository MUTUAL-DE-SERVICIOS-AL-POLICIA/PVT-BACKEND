<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Procedure\ProcedureModality;

class EcoComModality extends Model
{
    use HasFactory;
    public $timestamps = false;
    
    public function procedure_modality() {
        return $this->belongsTo(ProcedureModality::class);
    }

}
