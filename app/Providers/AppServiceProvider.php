<?php

namespace App\Providers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Relations\Relation;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
         // JSON response wihtout data key
         JsonResource::withoutWrapping();

         // Localization
         setlocale(LC_TIME, env('APP_LC_TIME', 'es_BO.utf8'));
         Carbon::setLocale(env('APP_LOCALE', 'es'));
 
         // Custom validations
         Validator::extend('alpha_spaces', function ($attribute, $value) {
             return preg_match('/^[\pL\s]+$/u', $value);
         });

         // Polymorphic relationships
        Relation::morphMap([
            'affiliates' => 'App\Models\Affiliate\Affiliate',
            'addresses' => 'App\Models\Affiliate\Address',
            'spouses' => 'App\Models\Affiliate\Spouse',
            'users' => 'App\Models\Admin\User',
            'roles' => 'App\Models\Admin\Role',
            'modules' => 'App\Models\Admin\Module',
            'loans' => 'App\Models\Loan\Loan',
            'notes' => 'App\Models\Note',
            'sismus' => 'App\Models\Loan\Sismu',
            'procedure_types' => 'App\Models\Procedure\ProcedureType',
            'loan_payments' => 'App\Models\Loan\LoanPayment',
            'vouchers' => 'App\Models\Voucher',
            'aid_contributions' => 'App\Models\Contribution\AidContribution',
            'loan_contribution_adjusts' => 'App\Models\Loan\LoanContributionAdjust',
            'retirement_funds' => 'App\Models\RetirementFund\RetirementFund',
            'quota_aid_mortuaries' => 'App\Models\QuotaAidMortuary\QuotaAidMortuary',
            'contribution_processes' => 'App\Models\Contribution\ContributionProcess',
            'contributions' => 'App\Models\Contribution\Contribution',
            'reimbursements' => 'App\Models\Contribution\Reimbursement',
            'aid_reimbursements' => 'App\Models\Contribution\AidReimbursement',
            'wf_states' => 'App\Models\Workflow\WfState',
            'ret_fun_beneficiaries' => 'App\Models\RetirementFund\RetFunBeneficiary',
            'quota_aid_beneficiaries' => 'App\Models\QuotaAidMortuary\QuotaAidBeneficiary',
            'economic_complements' => 'App\Models\EconomicComplement\EconomicComplement',
            'discount_type_economic_complement'=> 'App\Models\EconomicComplement\DiscountTypeEconomicComplement'
        ]);
    }
}
