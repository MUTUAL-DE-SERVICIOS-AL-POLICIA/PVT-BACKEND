<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Model;

class LoanGlobalParameter extends Model
{
    //use Traits\EloquentGetTableNameTrait;
    public $timestamps = true;

    protected $fillable = [
        'offset_ballot_day',
        'offset_interest_day',
        'livelihood_amount',
        'days_current_interest',
        'min_amount_fund_rotary',
        'numerator',
        'denominator'
    ];
}