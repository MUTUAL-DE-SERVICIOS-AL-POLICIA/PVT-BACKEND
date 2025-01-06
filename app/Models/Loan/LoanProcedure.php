<?php

namespace App\Models\Loan;

use App\Models\Loan\LoanGlobalParameter;

use Illuminate\Database\Eloquent\Model;

class LoanProcedure extends Model
{
    public $timestamps = true;
    public $fillable = [
        'description',
        'start_production_date',
        'end_production_date',
    ];

    public function loan_global_parameter()
    {
        return $this->HasOne(LoanGlobalParameter::class);
    }
}
