<?php

namespace App\Models\Procedure;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Loan\LoanProcedure;
use App\Models\Loan\LoanInterest;
use App\Models\Loan\LoanModalityParameter;
use App\Models\Workflow\Workflow;

class ProcedureModality extends Model
{
    use HasFactory;
    public $timestamps = false;
    public $guarded = ['id'];
    protected $fillable = [
        'procedure_type_id',
        'name', 
        'shortened',
        'is_valid',
        'workflow_id'
    ];

    public function procedure_type()
    {
        return $this->belongsTo(ProcedureType::class);
    }

    public function procedure_requirements()
	{
		return $this->hasMany(ProcedureRequirement::class);
    }

    public function getLoanModalityParameterAttribute()
    {
        $loan_procedure = LoanProcedure::where('is_enable', true)->first()->id;
        return LoanModalityParameter::where('procedure_modality_id', $this->id)->where('loan_procedure_id', $loan_procedure)->first();
    }

    public function getCurrentInterestAttribute()
    {
        return $this->loan_interests()->first();
    }

    public function loan_interests()
    {
        return $this->hasMany(LoanInterest::class)->latest();
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
