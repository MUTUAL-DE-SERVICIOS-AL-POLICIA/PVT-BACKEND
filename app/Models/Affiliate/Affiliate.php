<?php

namespace App\Models\Affiliate;

use App\Helpers\Util;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FinancialEntity;
use App\Models\Contribution\PayrollSenasir;
use App\Models\Admin\User;
use App\Models\Affiliate\Unit;
use App\Models\Contribution\Contribution;
use App\Models\Contribution\Reimbursement;
use App\Models\Affiliate\Address;
use App\Models\Affiliate\AffiliateToken;
use App\Models\City;
use App\Models\Contribution\PayrollCommand;
use App\Models\Observation;

class Affiliate extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $relationships = ['City'];
    protected $fillable = [
        'user_id',
        'affiliate_state_id',
        'city_identity_card_id',
        'city_birth_id',
        'degree_id',
        'unit_id',
        'category_id',
        'pension_entity_id',
        'identity_card',
        'registration',
        'type',
        'last_name',
        'mothers_last_name',
        'first_name',
        'second_name',
        'surname_husband',
        'gender',
        'civil_status',
        'birth_date',
        'date_entry',
        'date_death',
        'reason_death',
        'date_derelict',
        'reason_derelict',
        'phone_number',
        'cell_phone_number',
        'nua',
        'created_at',
        'updated_at',
        'deleted_at',
        'service_years',
        'service_months',
        'death_certificate_number',
        'due_date',
        'is_duedate_undefined',
        'account_number',
        'financial_entity_id',
        'sigep_status',
        'unit_police_description',
        'id_person_senasir'
    ];

    public function affiliate_state()
    {
        return $this->belongsTo(AffiliateState::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function degree()
    {
        return $this->belongsTo(Degree::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function pension_entity()
    {
        return $this->belongsTo(PensionEntity::class);
    }
    public function financial_entity()
    {
        return $this->belongsTo(FinancialEntity::class);
    }
    public function payroll_senasir()
    {
        return $this->hasMany(PayrollSenasir::class);
    }
    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }
    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }
    public function addresses()
    {
        return $this->morphToMany(Address::class, 'addressable')->withPivot('validated')->withTimestamps()->latest('updated_at');
    }
    public function payroll_command()
    {
        return $this->hasMany(PayrollCommand::class);
    }
    public function spouse()
    {
        return $this->hasOne(Spouse::class);
    }
    public function affiliate_token()
    {
        return $this->hasOne(AffiliateToken::class, 'affiliate_id', 'id');
    }
    public function city_birth()
    {
        return $this->belongsTo(City::class, 'city_birth_id', 'id');
    }
    public function city_identity_card()
    {
        return $this->belongsTo(City::class, 'city_identity_card_id', 'id');
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function observations()
    {
        return $this->morphMany(Observation::class, 'observable')->latest('updated_at');
    }
    public function getFullNameAttribute()
    {
        return rtrim(preg_replace('/[[:blank:]]+/', ' ', join(' ', [$this->first_name, $this->second_name, $this->last_name, $this->mothers_last_name, $this->surname_husband])));
    }
    public function getDeadAttribute()
    {
        return ($this->date_death != null || $this->reason_death != null || $this->death_certificate_number != null || $this->affiliate_state->name == "Fallecido");
    }
    public function getCivilStatusGenderAttribute()
    {
        return Util::get_civil_status($this->civil_status, $this->gender);
    }
    public function getIdentityCardExtAttribute()
    {
        $data = $this->identity_card;
        if ($this->city_identity_card && $this->city_identity_card_id != 11) {
            $data .= ' ' . $this->city_identity_card->first_shortened;
        }
        return rtrim($data);
    }
}
