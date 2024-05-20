<?php

namespace App\Models;

use App\Models\Affiliate\Affiliate;
use App\Models\EconomicComplement\EcoComMovement;
use App\Models\EconomicComplement\EcoComProcedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devolution extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public $fillable = ['
        affiliate_id
        observation_type_id
        start_eco_com_procedure_id
        total
        balance
        deposit_number
        payment_amount
        payment_date
        percentage
        created_at
        updated_at
        deleted_at
        has_payment_commitment'];
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
    public function observation_type()
    {
        return $this->belongsTo(ObservationType::class);
    }
    public function dues()
    {
        return $this->hasMany(Due::class);
    }
    public function eco_com_procedure()
    {
        return $this->belongsTo(EcoComProcedure::class, 'start_eco_com_procedure_id');
    }
    public function eco_com_procedures()
    {
        return $this->belongsToMany(EcoComProcedure::class, 'devolution_eco_com_procedure', 'devolution_id', 'eco_com_procedure_id');
    }
    public function ecoComMovements()
    {
        return $this->morphMany(EcoComMovement::class, 'morphable');
    }
}
