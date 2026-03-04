<?php

namespace App\Models\EconomicComplement;

use App\Helpers\Util;
use App\Models\Admin\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoComProcedure extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'year',
        'semester',
        'normal_start_date',
        'normal_end_date',
        'lagging_start_date',
        'lagging_end_date',
        'additional_start_date',
        'additional_end_date',
        'indicator',
        'rent_month',
        'sequence'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getTextName()
    {
        return ($this->semester == 'Primer' ?  '1ER.' : '2DO.') ." SEMESTRE ". $this->getYear();
    }
    public function getYear()
    {
        return Carbon::parse($this->year)->year;
    }

    public function getActiveProcedures()
    {
        return EcoComProcedure::where('normal_start_date', '<=', now())
            ->where('normal_end_date', '>=', now())
            ->orderBy('year')
            ->get();
    }

    public function fullName()
    {
        return  Util::removeSpaces($this->semester.'/SEM/'.Carbon::parse($this->year)->year);
    }
}
