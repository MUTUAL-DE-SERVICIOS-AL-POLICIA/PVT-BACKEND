<?php

namespace App\Models\Loan;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanGuaranteeRegister extends Model
{
    use SoftDeletes;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'role_id',
        'loan_id',
        'affiliate_id',
        'guarantable_type',
        'guarantable_id',
        'amount',
        'period_date',
        'database_name',
        'loan_code_guarantee',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
    public function records()
    {
        return $this->morphMany(Record::class, 'recordable');
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
