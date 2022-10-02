<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sismu extends Model
{
    use SoftDeletes;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'code',
        'amount_approved',
        'loan_term',
        'balance',
        'estimated_quota',
        'date_cut_refinancing',
        'disbursement_date',
        'loan_id',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
    public function records()
    {
        return $this->belongsTo(Record::class, 'recordable');
    }
}
