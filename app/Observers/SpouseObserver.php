<?php

namespace App\Observers;

use App\Models\Affiliate\Spouse;
use App\Helpers\Util;
use App\Models\City;

class SpouseObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;
    /**
     * Handle the Spouse "created" event.
     *
     * @param  \App\Models\Spouse  $spouse
     * @return void
     */
    public function created(Spouse $spouse)
    {
        Util::save_record_affiliate($spouse->affiliate,'registró cónyugue');
    }

    /**
     * Handle the Spouse "updated" event.
     *
     * @param  \App\Models\Spouse  $spouse
     * @return void
     */
    public function updated(Spouse $spouse)
    {
        $message = 'modificó cónyugue';
        if($spouse->city_identity_card_id != $spouse->getOriginal('city_identity_card_id')) {
            $id = $spouse->getOriginal('city_identity_card_id');
            $old = City::find($id);
            $message = $message . ' [Lugar de expedición] '.($old->name??'Sin Expedición').' a '.($spouse->city_identity_card->name??'Sin Expedición').', ';
        }
        if($spouse->city_birth_id != $spouse->getOriginal('city_birth_id')) {
            $id = $spouse->getOriginal('city_birth_id');
            $old = City::find($id);
            $message = $message . ' [Ciudad de nacimiento] '.($old->name??'Sin ciudad').' a '.($spouse->city_birth->name??'Sin ciudad').', ';
        }
        Util::save_record_affiliate($spouse->affiliate,$message);
        Util::save_record_affiliate($spouse->affiliate,Util::concat_action($spouse,'modificó cónyugue'));
    }

    /**
     * Handle the Spouse "deleted" event.
     *
     * @param  \App\Models\Spouse  $spouse
     * @return void
     */
    public function deleted(Spouse $spouse)
    {
        //
    }

    /**
     * Handle the Spouse "restored" event.
     *
     * @param  \App\Models\Spouse  $spouse
     * @return void
     */
    public function restored(Spouse $spouse)
    {
        //
    }

    /**
     * Handle the Spouse "force deleted" event.
     *
     * @param  \App\Models\Spouse  $spouse
     * @return void
     */
    public function forceDeleted(Spouse $spouse)
    {
        //
    }
}
