<?php

namespace App\Models;

use App\Models\Admin\Module;
use App\Models\EconomicComplement\EconomicComplement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObservationType extends Model
{
    use HasFactory;

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
    public function observations()
    {
        return $this->hasMany(Observation::class);
    }
    public function economic_complements() {
        return $this->morphedByMany(EconomicComplement::class, 'observable');
    }
}
