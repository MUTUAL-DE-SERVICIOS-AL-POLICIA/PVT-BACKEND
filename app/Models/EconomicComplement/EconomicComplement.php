<?php

namespace App\Models\EconomicComplement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Affiliate\Affiliate;
use App\Models\ObservationType;
use App\Models\Notification\NotificationSend;

class EconomicComplement extends Model
{
    use HasFactory;

    public function affiliate() {
        return $this->belongsTo(Affiliate::class);
    }

    public function eco_com_procedure() {
        return $this->belongsTo(EcoComProcedure::class);
    }

    public function eco_com_modality() {
        return $this->belongsTo(EcoComModality::class);
    }

    public function eco_com_beneficiary(){
        return $this->hasOne(EcoComBeneficiary::class);
    }

    public function observations() {
        return $this->morphToMany(ObservationType::class, 'observable')->whereNull('observables.deleted_at')->withPivot(['user_id', 'message', 'date', 'enabled'])->withTimestamps();
    }

    public function eco_com_state() {
        return $this->belongsTo(EcoComState::class);
    }

    public function sends() {
        return $this->morphMany(NotificationSend::class, 'sendable');
    }
}
