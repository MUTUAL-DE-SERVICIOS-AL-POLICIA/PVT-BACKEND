<?php

namespace App\Observers;

use App\Models\Affiliate\AffiliateUser;
use App\Models\Affiliate\Affiliate;
use App\Helpers\Util;


class AffiliateUserObserver
{
    /**
     * Handle the AffiliateUser "created" event.
     *
     * @param  \App\Models\AffiliateUser  $affiliateUser
     * @return void
     */
    public function created(AffiliateUser $affiliateUser)
    {
         $Affiliate = Affiliate::find($affiliateUser->affiliate_token->affiliate_id);
         Util::save_record_affiliate($Affiliate,'registró credenciales');
    }

    /**
     * Handle the AffiliateUser "updated" event.
     *
     * @param  \App\Models\AffiliateUser  $affiliateUser
     * @return void
     */
    public function updated(AffiliateUser $affiliateUser)
    {
        $Affiliate = Affiliate::find($affiliateUser->affiliate_token->affiliate_id);
        $message = 'Actualizó';
        if($affiliateUser->username != $affiliateUser->getOriginal('username')) {
            $message = $message . ' [Username] '.($affiliateUser->getOriginal('username')??"Sin username").' a '.($affiliateUser->username??"Sin username").', ';
        }
        if($affiliateUser->password != $affiliateUser->getOriginal('password')) {
            $message = $message . ' [Password] ';
        }

        if($affiliateUser->access_status != $affiliateUser->getOriginal('access_status')) {
            $message = $message . ' [Estado de acceso] '.($affiliateUser->getOriginal('access_status')??"Estado de acceso").' a '.($affiliateUser->access_status??"Estado de acceso").', ';
        }
         Util::save_record_affiliate($Affiliate,$message);
    }

    /**
     * Handle the AffiliateUser "deleted" event.
     *
     * @param  \App\Models\AffiliateUser  $affiliateUser
     * @return void
     */
    public function deleted(AffiliateUser $affiliateUser)
    {
        //
    }

    /**
     * Handle the AffiliateUser "restored" event.
     *
     * @param  \App\Models\AffiliateUser  $affiliateUser
     * @return void
     */
    public function restored(AffiliateUser $affiliateUser)
    {
        //
    }

    /**
     * Handle the AffiliateUser "force deleted" event.
     *
     * @param  \App\Models\AffiliateUser  $affiliateUser
     * @return void
     */
    public function forceDeleted(AffiliateUser $affiliateUser)
    {
        //
    }
}
