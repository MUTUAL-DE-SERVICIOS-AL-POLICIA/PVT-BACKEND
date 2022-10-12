<?php

namespace App\Models\Loan;

use App\Helpers\Util;
use App\Models\Admin\Role;
use App\Models\Affiliate\Affiliate;
use App\Models\City;
use App\Models\FinancialEntity;
use App\Models\Note;
use App\Models\Observation;
use App\Models\PersonalReference;
use App\Models\Procedure\ProcedureDocument;
use App\Models\Procedure\ProcedureModality;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Notification\NotificationSend;

class Loan extends Model
{
    use SoftDeletes;

    protected $dates = [
        'request_date'
    ];
    public $timestamps = true;
    public $guarded = ['id'];
    public $fillable = [
        'code',
        'procedure_modality_id',
        'disbursement_date',
        'num_accounting_voucher',
        'parent_loan_id',
        'parent_reason',
        'request_date',
        'amount_requested',
        'city_id',
        'interest_id',
        'state_id',
        'amount_approved',
        'indebtedness_calculated',
        'indebtedness_calculated_previous',
        'liquid_qualification_calculated',
        'loan_term',
        'refinancing_balance',
        'guarantor_amortizing',
        'payment_type_id',
        'number_payment_type',
        'destiny_id',
        'financial_entity_id',
        'role_id',
        'property_id',
        'validated',
        'user_id',
        'delivery_contract_date',
        'return_contract_date',
        'regional_delivery_contract_date',
        'regional_return_contract_date',
        'payment_plan_compliance',
        'affiliate_id'
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class,'affiliate_id','id');
    }
    public function loan_plan()
    {
        return $this->hasMany(LoanPlanPayment::class)->orderBy('quota_number');
    }
    public function loan_property()
    {
        return $this->belongsTo(LoanProperty::class, 'property_id','id');
    }
    public function notes()
    {
        return $this->morphMany(Note::class, 'annotable');
    }
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')->withPivot('user_id', 'date')->withTimestamps();
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function state()
    {
      return $this->belongsTo(LoanState::class, 'state_id','id');
    }
    public function city()
    {
        return $this->belongsTo(City::class,'city_id','id');
    }
    public function payment_type()
    {
        return $this->belongsTo(PaymentType::class,'payment_type_id','id');
    }
    public function financial_entity()
    {
        return $this->belongsTo(FinancialEntity::class,'financial_entity_id','id');
    }
    public function submitted_documents()
    {
        return $this->belongsToMany(ProcedureDocument::class, 'loan_submitted_documents', 'loan_id')->withPivot('reception_date', 'comment', 'is_valid');
    }
    public function guarantors()
    {
        return $this->belongsToMany(Affiliate:: class, 'loan_guarantors');
    }
    public function loan_persons()
    {
        return $this->belongsToMany(PersonalReference::class, 'loan_persons');
    }
    public function personal_references()
    {
        return $this->loan_persons()->withPivot('cosigner')->whereCosigner(false);
    }
    public function cosigners()
    {
        return $this->loan_persons()->withPivot('cosigner')->whereCosigner(true);
    }
    public function modality()
    {
        return $this->belongsTo(ProcedureModality::class,'procedure_modality_id', 'id');
    }
    public function payments()
    {
        return $this->hasMany(LoanPayment::class)->orderBy('quota_number', 'desc')->orderBy('created_at');
    }
    public function loan_contribution_adjusts()
    {
        return $this->hasMany(LoanContributionAdjust::class);
    }
    public function interest()
    {
        return $this->belongsTo(LoanInterest::class, 'interest_id', 'id');
    }
    public function data_loan()
    {
        return $this->hasOne(Sismu::class,'loan_id','id');
    }
    public function observations()
    {
        return $this->morphMany(Observation::class, 'observable');
    }
    public function disbursable()
    {
        return $this->morphTo();
    }
    public function destiny()
    {
        return $this->belongsTo(LoanDestiny::class, 'destiny_id', 'id');
    }
    public function records()
    {
        return $this->morphMany(Record::class, 'recordable')->latest('updated_at');
    }
    public function loan_guarantee_registers()
    {
        return $this->hasMany(LoanGuaranteeRegister::class);
    }
    public function borrower(){
        return $this->hasOne(LoanBorrower::class);
    }
    public function getEstimatedQuotaAttribute()
    {
        $monthly_interest = $this->interest->monthly_current_interest;
        unset($this->interest);
        return Util::round2($monthly_interest * $this->amount_approved / (1 - 1 / pow((1 + $monthly_interest), $this->loan_term)));
    }

    public function sends() {
        return $this->morphMany(NotificationSend::class, 'sendable');
    }

}
