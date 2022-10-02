<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanState extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $guarded = ['id'];
    public $fillable = ['name', 'description'];

    public function loans()
    {
        return $this->hasMany(Loan::class, 'state_id', 'id');
    }
}
