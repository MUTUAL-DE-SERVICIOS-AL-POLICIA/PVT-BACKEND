<?php

namespace App\Models\Loan;

use App\Models\Admin\User;
use App\Models\Affiliate\Affiliate;
use App\Models\Loan\Loan;
use App\Models\Loan\Record;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanContributionAdjust extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'loan_id',
        'affiliate_id',
        'adjustable_id',
        'adjustable_type',
        'type_affiliate',
        'amount',
        'type_adjust',
        'period_date',
        'description',
        'database_name'
    ]; 

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
    public function adjustable()    
    {
        return $this->morphTo();
    }
    public function records()     
    {
        return $this->morphMany(Record::class, 'recordable');
    }
}