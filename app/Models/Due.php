<?php

namespace App\Models;

use App\Models\EconomicComplement\EcoComProcedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Due extends Model
{
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'devolution_id',
        'eco_com_procedure_id',
        'amount'
    ];
    use HasFactory;
    public function devolution()
    {
        return $this->belongsTo(Devolution::class);
    }
    public function eco_com_procedure()
    {
        return $this->belongsTo(EcoComProcedure::class);
    }
}
