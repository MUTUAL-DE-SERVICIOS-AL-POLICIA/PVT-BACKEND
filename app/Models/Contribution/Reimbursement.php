<?php

namespace App\Models\Contribution;

use Illuminate\Database\Eloquent\Model;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\Degree;
use App\Models\Admin\User;
use App\Models\Affiliate\Breakdown;
use App\Models\Affiliate\Unit;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reimbursement extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'affiliate_id', 
        'degree_id', 
        'unit_id', 
        'breakdown_id', 
        'month_year',
        'type',
        'base_wage',
        'seniority_bonus',
        'study_bonus',
        'position_bonus',
        'border_bonus',
        'east_bonus',
        'public_security_bonus',
        'gain',
        'payable_liquid',
        'quotable',
        'retirement_fund',
        'mortuary_quota',
        'subtotal',
        'ipc',
        'total',
        'created_at',
        'updated_at',
        'deleted_at',
        'interest',
        'valid'
    ];

    public function affiliate()                      
    {
        return $this->belongsTo(Affiliate::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function degree()
    {
        return $this->belongsTo(Degree::class);
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function breakdown()
    {
        return $this->belongsTo(Breakdown::class);
    }
}
