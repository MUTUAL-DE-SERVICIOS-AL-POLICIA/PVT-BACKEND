<?php

namespace App\Models\RetirementFund;

use App\Models\Admin\User;
use App\Models\ObservationType;
use App\Models\RetirementFund\RetirementFund;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetFunObservation extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function ret_fun()  
    {
        return $this->belongsTo(RetirementFund::class,'retirement_fund_id','id');
    }
    public function observation_type()
    {
        return $this->belongsTo(ObservationType::class);
    }
}
