<?php

namespace App\Models\QuotaAidMortuary;

use App\Models\Admin\User;
use App\Models\Affiliate\Affiliate;
use App\Models\City;
use App\Models\DiscountType;
use App\Models\Procedure\ProcedureModality;
use App\Models\Tag;
use App\Models\Workflow\WfRecord;
use App\Models\Workflow\WfState;
use App\Models\Workflow\Workflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotaAidMortuary extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'affiliate_id',
        'quota_aid_procedure_id',
        'procedure_modality_id',
        'city_start_id',
        'city_end_id',
        'code',
        'reception_date',
        'subtotal',
        'total', 
        'workflow_id',
        'wf_state_current_id',
        'inbox_state',
        'procedure_state_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
    public function quota_aid_procedure()
    {
        return $this->belongsTo(QuotaAidProcedure::class);
    }
    public function procedure_modality()
    {
        return $this->belongsTo(ProcedureModality::class, 'procedure_modality_id');
    }
    public function city_start()    
    {
        return $this->belongsTo(City::class, 'city_start_id');
    }
    public function city_end()
    {
        return $this->belongsTo(City::class, 'city_end_id');
    }
    public function quota_aid_submitted_document()
	{
		return $this->hasMany(QuotaAidSubmittedDocument::class);
    }
    public function quota_aid_observation()
    {
        return $this->hasMany(QuotaAidObservation::class);
    }
    public function quota_aid_beneficiaries()
    {
        return $this->hasMany(QuotaAidBeneficiary::class);
    }
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
    public function wf_state()
    {
        return $this->belongsTo(WfState::class, 'wf_state_current_id', 'id');
    }
    public function tags()     
    {
        return $this->morphToMany(Tag::class, 'taggable')->withPivot(['user_id','date'])->withTimestamps();
    }
    public function discount_types()
    {
        return $this->belongsToMany(DiscountType::class)->withPivot(['amount', 'date', 'code', 'note_code', 'note_code_date'])->withTimestamps();
    }
    public function quota_aid_correlative()
    {
        return $this->hasMany(QuotaAidCorrelative::class);
    }
    public function wf_records()        
    {
        return $this->morphMany(WfRecord::class, 'recordable');
    }
}
