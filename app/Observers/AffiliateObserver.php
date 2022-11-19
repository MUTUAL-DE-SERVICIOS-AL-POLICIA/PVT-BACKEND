<?php

namespace App\Observers;

use App\Models\Affiliate\Affiliate;
use App\Helpers\Util;
use App\Models\Affiliate\PensionEntity;
use App\Models\Affiliate\AffiliateState;
use App\Models\Affiliate\AffiliateToken;
use App\Models\Affiliate\AffiliateUser;
use App\Models\Affiliate\Category;
use App\Models\Affiliate\Degree;
use App\Models\City;
use App\Models\FinancialEntity;

class AffiliateObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;
    /**
     * Handle the Affiliate "created" event.
     *
     * @param  \App\Models\Affiliate  $affiliate
     * @return void
     */
    public function created(Affiliate $affiliate)
    {
       Util::save_record_affiliate($affiliate,'registró');
    }

    /**
     * Handle the Affiliate "updated" event.
     *
     * @param  \App\Models\Affiliate  $affiliate
     * @return void
     */
    public function updated(Affiliate $affiliate)
    {
        $message = 'modificó';
        if($affiliate->pension_entity_id != $affiliate->getOriginal('pension_entity_id')) {
            $id = $affiliate->getOriginal('pension_entity_id');
            $old = PensionEntity::find($affiliate->getOriginal('pension_entity_id'));
            $message = $message . ' [Ente Gestor] '.($old->name??"Sin ente gestor").' a '.(optional($affiliate->pension_entity)->name??"Sin ente gestor").', ';
        }
        if($affiliate->city_identity_card_id != $affiliate->getOriginal('city_identity_card_id')) {
            $id = $affiliate->getOriginal('city_identity_card_id');
            $old = City::find($id);
            $message = $message . ' [Lugar de expedición] '.($old->name??'Sin Expedición').' a '.($affiliate->city_identity_card->name??'Sin Expedición').', ';
        }
        if($affiliate->financial_entity_id != $affiliate->getOriginal('financial_entity_id')) {
            $id = $affiliate->getOriginal('financial_entity_id');
            $old = FinancialEntity::find($id);
            $message = $message . ' [Entidad financiera] '.($old->name?? 'Sin entidad financiera').' a '.($affiliate->financial_entity->name??'Sin entidad financiera').', ';
        }
        if($affiliate->city_birth_id != $affiliate->getOriginal('city_birth_id')) {
            $id = $affiliate->getOriginal('city_birth_id');
            $old = City::find($id);
            $message = $message . ' [Ciudad de nacimiento] '.($old->name??'Sin ciudad').' a '.($affiliate->city_birth->name??'Sin ciudad').', ';
        }
        if($affiliate->affiliate_state_id != $affiliate->getOriginal('affiliate_state_id'))
        {
            $id = $affiliate->getOriginal('affiliate_state_id');
            $old = AffiliateState::find($id);
            $message = $message . ' [Estado] '.($old->name ?? 'Sin Estado').' a '.($affiliate->affiliate_state->name ?? 'Sin Estado').', ';
            if ($affiliate->affiliate_state_id==4) {
                $affiliateToken=AffiliateToken::where('affiliate_id',$affiliate->id)->first();
                if ($affiliateToken) {
                    $affiliateUser=AffiliateUser::where('affiliate_token_id',$affiliateToken->id)->first();
                    if ($affiliateUser) {
                        $affiliateUser->access_status='Inactivo';
                        $affiliateUser->save();
                    }
                }
            }
        }
        if($affiliate->category_id != $affiliate->getOriginal('category_id'))
        {
            $id = $affiliate->getOriginal('category_id');
            $old = Category::find($id);
            $message = $message . ' [Categoría] '.($old->name ?? 'sin categoría' ).' a '.($affiliate->category->name ?? 'sin categoría' ).', ';
        }
        if($affiliate->degree_id != $affiliate->getOriginal('degree_id'))
        {
            $id = $affiliate->getOriginal('degree_id');
            $old = Degree::find($id);
            $message = $message . ' [Grado] '.optional($old)->name.' a '.optional($affiliate->degree)->name.', ';
        }

        Util::save_record_affiliate($affiliate,$message);
        Util::save_record_affiliate($affiliate,Util::concat_action($affiliate));
    }

    /**
     * Handle the Affiliate "deleted" event.
     *
     * @param  \App\Models\Affiliate  $affiliate
     * @return void
     */
    public function deleted(Affiliate $affiliate)
    {
        Util::save_record_affiliate($affiliate,'eliminó');
    }

    /**
     * Handle the Affiliate "restored" event.
     *
     * @param  \App\Models\Affiliate  $affiliate
     * @return void
     */
    public function restored(Affiliate $affiliate)
    {
        //
    }

    /**
     * Handle the Affiliate "force deleted" event.
     *
     * @param  \App\Models\Affiliate  $affiliate
     * @return void
     */
    public function forceDeleted(Affiliate $affiliate)
    {
        //
    }
}
