<?php

namespace App\Models\Loan;

use App\Models\Loan\Loan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    use HasFactory;
    
    public $timestamps = true;
    public $fillable = ['name'];
    public $guarded = ['id'];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
