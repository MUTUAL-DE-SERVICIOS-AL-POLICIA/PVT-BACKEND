<?php

namespace App\Models\QuotaAidMortuary;

use App\Models\Affiliate\Address;
use App\Models\QuotaAidMortuary\QuotaAidAdvisor;
use App\Models\Affiliate\Kinship;
use App\Models\City;
use App\Models\Testimony;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotaAidBeneficiary extends Model
{
    use HasFactory;
    
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'quota_aid_mortuary_id',
        'city_identity_card_id',
        'kinship_id',
        'identity_card',
        'last_name',
        'mothers_last_name',
        'first_name',
        'second_name',
        'surname_husband',
        'birth_date',
        'gender', 
        'civil_status',
        'percentage',
        'paid_amount',
        'phone_number',
        'cel_phone_number',
        'state'
    ];

    public function quota_aid_mortuary()
    {
        return $this->belongsTo(QuotaAidMortuary::class);
    }
    public function city_identity_card()    
    {
        return $this->belongsTo(City::class, 'city_identity_card_id','id');
    }
    public function kinship()
    {
        return $this->belongsTo(Kinship::class);
    }
    public function address()     //revisar
    {
        return $this->morphToMany(Address::class, 'addressable')->withTimestamps();
    }
    public function quota_aid_advisors()
    {
        return $this->belongsToMany(QuotaAidAdvisor::class, 'quota_aid_advisor_beneficiary', 'quota_aid_beneficiary_id', 'quota_aid_advisor_id');
    }
    public function quota_aid_legal_guardians()
    {
        return $this->belongsToMany(QuotaAidLegalGuardian::class, 'quota_aid_beneficiary_legal_guardian', 'quota_aid_beneficiary_id', 'quota_aid_legal_guardian_id');
    }
    public function testimonies()
    {
        return $this->belongsToMany(Testimony::class)->withTimestamps();
    }
    public function getFullNameAttribute()
    {
      return rtrim(preg_replace('/[[:blank:]]+/', ' ', join(' ', [$this->first_name, $this->second_name, $this->last_name, $this->mothers_last_name,$this->surname_husband])));
    }
}
