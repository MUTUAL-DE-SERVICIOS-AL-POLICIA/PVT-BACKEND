<?php

namespace App\Models\Contribution;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Affiliate\Affiliate;
use App\Models\Contribution\ContributionPassive;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollSenasir extends Model
{
    protected $table = "payroll_senasirs";
    use HasFactory;
    use SoftDeletes;
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'affiliate_id',
        'year_p',
        'month_p',
        'id_person_senasir',
        'registration_a',
        'registration_s',
        'department',
        'regional',
        'rent',
        'rent_type',
        'identity_card',
        'last_name',
        'mothers_last_name',
        'first_name',
        'second_name',
        'surname_husband',
        'birth_date',
        'rent_class',
        'total_won',
        'total_discounts',
        'payable_liquid',
        'refund_r_basic',
        'dignity_rent',
        'refund_dignity_rent',
        'refund_bonus',
        'refund_additional_amount',
        'refund_inc_management',
        'discount_contribution_muserpol',
        'discount_covipol',
        'discount_loan_muserpol',
        'identity_card_a',
        'last_name_a',
        'mothers_last_name_a',
        'first_name_a',
        'second_name_a',
        'surname_husband_a',
        'birth_date_a',
        'rent_class_a',
        'date_death_a',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

      //relacion de la tabla polimorfica 
    public function payroll_senasir_contribution()
    {
        return $this->morphMany(ContributionPassive::class,'contributionable');
    }

    public static function data_period($month,$year)
    {
        $data = collect([]);
        $exists_data = true;
        $payroll =  PayrollSenasir::whereMonth_p($month)->whereYear_p($year)->count('id');
        if($payroll == 0) $exists_data = false;

        $data['exist_data'] = $exists_data;
        $data['count_data'] = $payroll;

        return  $data;
    }
}
