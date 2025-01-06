<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procedure\ProcedureModality;
use App\Models\Loan\LoanProcedure;

class LoanModalityParameter extends Model
{
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    public $fillable = [
        'procedure_modality_id',
        'debt_index',
        'quantity_ballots',
        'guarantors',
        'max_lenders',
        'min_guarantor_category',
        'max_guarantor_category',
        'min_lender_category',
        'max_lender_category',
        'max_cosigner',
        'personal_reference',
        'maximum_amount_modality',
        'minimum_amount_modality',
        'maximum_term_modality',
        'minimum_term_modality',
        'print_contract_platform',
        'print_receipt_fund_rotary',
        'print_form_qualification_platform',
        'loan_procedure_id',
        'max_approved_amount',
        'guarantor_debt_index',
        'loan_month_term',
        'coverage_percentage',
        'eval_percentage',
        'suggested_debt_index'
    ];

    public function getDecimalIndexAttribute()
    {
        return $this->debt_index / (100);
    }

    public function getDecimalIndexSuggestedAttribute()
    {
        return $this->suggested_debt_index / (100);
    }

    public function procedure_modality()
    {
        return $this->belongsTo(ProcedureModality::class);
    }

    public function loan_procedure()
    {
        return $this->hasOne(LoanProcedure::class, 'id', 'loan_procedure_id');
    }
}