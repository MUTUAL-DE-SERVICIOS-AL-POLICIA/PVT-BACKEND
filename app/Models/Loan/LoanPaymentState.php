<?php

namespace App\Models\Loan;

use App\Models\Loan\LoanPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPaymentState extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $guarded = ['id'];
    public $fillable = ['name', 'description'];

    public function loan_payments()
	{
		return $this->hasMany(LoanPayment::class,'state_id','id');
    }
}