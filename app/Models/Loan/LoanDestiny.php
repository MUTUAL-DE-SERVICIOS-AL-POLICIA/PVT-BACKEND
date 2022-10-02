<?php

namespace App\Models\Loan;

use App\Models\Procedure\ProcedureType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDestiny extends Model
{
    public $timestamps = true;
    public $fillable = ['name', 'description'];

    public function procedure_types()
    {
        return $this->belongsToMany(ProcedureType::class);
    }
}
