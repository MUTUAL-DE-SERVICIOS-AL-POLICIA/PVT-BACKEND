<?php

namespace App\Models\RetirementFund;

use App\Models\Admin\User;
use App\Models\Affiliate\Affiliate;
use App\Models\City;
use App\Models\Contribution\ContributionType;
use App\Models\DiscountType;
use App\Models\Observation;
use App\Models\Procedure\ProcedureModality;
use App\Models\Tag;
use App\Models\Workflow\WfRecord;
use App\Models\Workflow\WfState;
use App\Models\Workflow\Workflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetirementFund extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'affiliate_id',
        'procedure_modality_id',
        'ret_fun_procedure_id',
        'city_start_id',
        'city_end_id',
        'workflow_id',
        'wf_state_current_id',
        'code',
        'reception_date',
        'average_quotable',
        'subtotal_ret_fun',
        'total_ret_fun',
        'subtotal_availability',
        'total_availability',
        'total',
        'ret_fun_state_id',
        'inbox_state'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
    public function procedure_modality()
    {
        return $this->belongsTo(ProcedureModality::class, 'procedure_modality_id');
    }
    public function ret_fun_procedure()
    {
        return $this->belongsTo(RetFunProcedure::class);
    }
    public function city_start()
    {
        return $this->belongsTo(City::class, 'city_start_id');
    }
    public function city_end()
    {
        return $this->belongsTo(City::class, 'city_end_id');
    }
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
    public function wf_state()
    {
        return $this->belongsTo(WfState::class, 'wf_state_current_id', 'id');
    }
    public function ret_fun_state()
    {
        return $this->belongsTo(RetFunState::class, 'ret_fun_state_id');
    }
    public function ret_fun_observations()
    {
        return $this->hasMany(RetFunObservation::class);
    }
    public function ret_fun_beneficiaries()
    {
        return $this->hasMany(RetFunBeneficiary::class);
    }
    public function discount_types()
    {
        return $this->belongsToMany(DiscountType::class)->withPivot(['amount', 'date', 'code', 'note_code', 'note_code_date'])->withTimestamps();
    }
    public function ret_fun_records()
    {
        return $this->hasMany(RetFunRecord::class, 'ret_fun_id', 'id');
    }
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')->withPivot(['user_id', 'date'])->withTimestamps();
    }
    public function contribution_types()
    {
        return $this->belongsToMany(ContributionType::class)->withPivot(['message'])->withTimestamps();
    }
    public function ret_fun_correlative()
    {
        return $this->hasMany(RetFunCorrelative::class);
    }
    public function wf_records()
    {
        return $this->morphMany(WfRecord::class, 'recordable');
    }
    public function observations()
    {
        return $this->morphMany(Observation::class, 'observable');
    }
}
