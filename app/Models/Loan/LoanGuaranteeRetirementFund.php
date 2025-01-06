<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Model;

class LoanGuaranteeRetirementFund extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    public $fillable = [
        'loan_id',
        'retirement_fund_average_id'
    ];
    public function loan()
    {
        return $this->belongsTo(Loan::class,'loan_id');
    }

    public function retirementFundAverage()
    {
        return $this->belongsTo(RetirementFundAverage::class, 'retirement_fund_average_id');
    }
}
