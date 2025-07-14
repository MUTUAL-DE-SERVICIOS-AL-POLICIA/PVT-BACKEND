<?php

namespace App\Models\EconomicComplement;

use App\Helpers\Util;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoComBeneficiary extends Model
{
    use HasFactory;

    protected $table = 'eco_com_applicants';

    public function economic_complement() {
        return $this->belongsTo(EconomicComplement::class);
    }

    public function getFullNameAttribute()
    {
        return rtrim(preg_replace('/[[:blank:]]+/', ' ', join(' ', [$this->first_name, $this->second_name, $this->last_name, $this->mothers_last_name, $this->surname_husband])));
    }

    public function ciWithExt()
    {
        return Util::removeSpaces($this->identity_card . ' ' .($this->city_identity_card->first_shortened ?? ''));
    }
}
