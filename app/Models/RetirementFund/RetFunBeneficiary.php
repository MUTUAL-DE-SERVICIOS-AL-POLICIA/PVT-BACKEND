<?php

namespace App\Models\RetirementFund;

use App\Models\Affiliate\Address;
use App\Models\Affiliate\Kinship;
use App\Models\RetirementFund\RetirementFund;
use App\Models\RetirementFund\RetFunAdvisor;
use App\Models\RetirementFund\RetFunLegalGuardian;
use App\Models\City;
use App\Models\Testimony;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetFunBeneficiary extends Model
{
    use HasFactory;

    public function retirement_fund()
    {
        return $this->belongsTo(RetirementFund::class);
    }
    public function city_identity_card()
    {
        return $this->belongsTo(City::class);
    }
    public function kinship()
    {
        return $this->belongsTo(Kinship::class);
    }
    public function ret_fun_advisors()   //revisar
    {
        return $this->belongsToMany(RetFunAdvisor::class,'ret_fun_advisor_beneficiary','ret_fun_beneficiary_id','ret_fun_advisor_id');
    }
    public function legal_guardians()     //revisar
    {
        return $this->belongsToMany(RetFunLegalGuardian::class, 'ret_fun_legal_guardian_beneficiary', 'ret_fun_beneficiary_id', 'ret_fun_legal_guardian_id');
    }
    public function address()   
    {
        return $this->morphToMany(Address::class, 'addressable')->withTimestamps();
    }
    public function testimony()
    {
        return $this->belongsToMany(Testimony::class)->withTimestamps();
    }
    public function getFullNameAttribute()
    {
      return rtrim(preg_replace('/[[:blank:]]+/', ' ', join(' ', [$this->first_name, $this->second_name, $this->last_name, $this->mothers_last_name,$this->surname_husband])));
    }
}
