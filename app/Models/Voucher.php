<?php

namespace App\Models;

use App\Models\Loan\Record;
use App\Models\VoucherType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'voucher_type_id',
        'code',
        'total',
        'payment_date', 
        'bank_pay_number', 
        'payable_id', 
        'payable_type', 
        'description'
    ];
    
    public function payable()
    {
        return $this->morphTo();
    }
    public function voucher_type()
    {
        return $this->belongsTo(VoucherType::class);
    }
    public function records()
    {
        return $this->morphMany(Record::class, 'recordable')->latest('updated_at');
    }
}
