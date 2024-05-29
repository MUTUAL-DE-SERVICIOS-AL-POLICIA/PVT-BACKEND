<?php

namespace App\Observers;

use App\Models\EconomicComplement\EcoComMovement;
use App\Models\Loan\Record;

class EcoComMovementObserver
{
    /**
     * Handle the EcoComMovement "created" event.
     *
     * @param  \App\Models\EcoComMovement  $ecoComMovement
     * @return void
     */
    public function created(EcoComMovement $ecoComMovement)
    {
        $record_type_id = null;
        $action=null;
        switch ($ecoComMovement->movement_type) {
            case 'discount_type_economic_complement':
                $record_type_id = 10;
                $action="el ".$ecoComMovement->description;
                break;
            case 'devolutions':
                $record_type_id=4;
                $action="la ".$ecoComMovement->description;
                break;
            case 'eco_com_direct_payments':
                $record_type_id=10;
                $action="el ".$ecoComMovement->description;
                    break;
            default:
                break;
        }
        Record::create([
            'user_id' => auth()->id(),
            'role_id' => auth()->user()->role_id,
            'record_type_id' => $record_type_id,
            'recordable_id' => $ecoComMovement->id,
            'recordable_type' => $ecoComMovement->getTable(),
            'action' => 'Registró '.$action
        ]);
    }

    /**
     * Handle the EcoComMovement "updated" event.
     *
     * @param  \App\Models\EcoComMovement  $ecoComMovement
     * @return void
     */
    public function updated(EcoComMovement $ecoComMovement)
    {
        //
    }

    /**
     * Handle the EcoComMovement "deleted" event.
     *
     * @param  \App\Models\EcoComMovement  $ecoComMovement
     * @return void
     */
    public function deleted(EcoComMovement $ecoComMovement)
    {
        $record_type_id = null;
        $action = null;
        switch ($ecoComMovement->movement_type) {
            case 'discount_type_economic_complement':
                $record_type_id = 10;
                $action = "el ".$ecoComMovement->description;
                break;
            case 'devolutions':
                $record_type_id = 4;
                $action = "la ".$ecoComMovement->description;
                break;
            case 'eco_com_direct_payments':
                $record_type_id = 10;
                $action = "el ".$ecoComMovement->description;
                break;
            default:
                break;
        }
        Record::create([
            'user_id' => auth()->id(),
            'role_id' => auth()->user()->role_id,
            'record_type_id' => $record_type_id,
            'recordable_id' => $ecoComMovement->id,
            'recordable_type' => $ecoComMovement->getTable(),
            'action' => 'Eliminó '.$action
        ]);
    }

    /**
     * Handle the EcoComMovement "restored" event.
     *
     * @param  \App\Models\EcoComMovement  $ecoComMovement
     * @return void
     */
    public function restored(EcoComMovement $ecoComMovement)
    {
        //
    }

    /**
     * Handle the EcoComMovement "force deleted" event.
     *
     * @param  \App\Models\EcoComMovement  $ecoComMovement
     * @return void
     */
    public function forceDeleted(EcoComMovement $ecoComMovement)
    {
        //
    }
}
