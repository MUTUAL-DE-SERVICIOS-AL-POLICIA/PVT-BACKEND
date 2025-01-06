<?php

namespace App\Models\Loan;

use App\Models\Procedure\ProcedureModality;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanInterest extends Model
{
    public $timestamps = true;
    protected $fillable = ['procedure_modality_id', 'annual_interest','penal_interest'];
    public $guarded = ['id'];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function daily_current_interest($parameter)
    {
        return $this->annual_interest * $parameter / (100 * 360);
    }

    public function getDailyPenalInterestAttribute($parameter)
    {
        return $this->penal_interest * $parameter/ (100 * 360);
    }

    public function monthly_current_interest($parameter, $month_term)
    {
        return (($this->annual_interest * $parameter) / 100) / (12 / $month_term);
    }

    public function getMonthlyPenalInterestAttribute($parameter)
    {
        return $this->penal_interest ($parameter) / (100 * 12);
    }

    public function procedure_modality()
    {
        return $this->belongsTo(ProcedureModality::class);
    }
}