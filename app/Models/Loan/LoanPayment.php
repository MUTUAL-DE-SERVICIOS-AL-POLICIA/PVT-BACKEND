<?php

namespace App\Models\Loan;

use App\Models\Admin\User;
use App\Models\Affiliate\Affiliate;
use App\Models\Procedure\ProcedureModality;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanPayment extends Model
{
    use SoftDeletes;

    public $timestamps = true;
    public $guarded = ['id'];
    public $fillable = [
        'loan_id',
        'code',
        'procedure_modality_id',
        'estimated_date',
        'quota_number',
        'estimated_quota',
        'penal_remaining',
        'penal_payment',
        'interest_remaining',
        'interest_payment',
        'capital_payment',
        'interest_accumulated',
        'penal_accumulated',
        'previous_balance',
        'previous_payment_date',
        'state_id',
        'voucher',
        'paid_by',
        'role_id',
        'affiliate_id',
        'loan_payment_date',
        'validated',
        'description',
        'user_id',
        'categorie_id',
        'initial_affiliate',
        'state_affiliate',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
    public function voucher_treasury()
    {
        return $this->morphOne(Voucher::class, 'payable')->latest('updated_at');
    }
    public function modality()
    {
        return $this->belongsTo(ProcedureModality::class,'procedure_modality_id', 'id');
    }
    public function state()
    {
      return $this->belongsTo(LoanPaymentState::class, 'state_id','id');
    }
    public function records()
    {
        return $this->morphMany(Record::class, 'recordable')->latest('updated_at');
    }
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function users()
    {
        return $this->hasOne(User::class,'id','id');
    }
}
