<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Model;

class RetirementFundAverage extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'degree_id',
        'category_id',
        'retirement_fund_average',
        'is_active'
    ];

    public function loanGuaranteeRetirementFunds()
    {
        return $this->hasMany(LoanGuaranteeRetirementFund::class, 'retirement_fund_average_id');
    }
}