<?php

namespace App\Http\Controllers\Contribution;

use App\Http\Controllers\Controller;
use App\Models\Contribution\Contribution;
use App\Models\Contribution\ContributionRate;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;

class ContributionRateController extends Controller
{
    public static function add_contribution_rate_each_month(){
        $contribution_rate_previus=ContributionRate::orderbydesc('month_year')->first();
        $new_date = new DateTime($contribution_rate_previus->month_year);
        $new_date->add(new DateInterval('P1M'));
        $contribution_rate_previus->month_year = $new_date->format('Y-m-d');
        $contribution_rate_new=ContributionRate::create([
            'user_id' => $contribution_rate_previus->user_id,
            'month_year' => $contribution_rate_previus->month_year,
            'retirement_fund'=> $contribution_rate_previus->retirement_fund,
            'mortuary_quota'=> $contribution_rate_previus->mortuary_quota,
            'mortuary_aid'=>$contribution_rate_previus->mortuary_aid,
            'fcsspn'=> $contribution_rate_previus->fcsspn
        ]);
        if ($contribution_rate_new) {
            return response()->json(['message' => 'Nuevo registro creado con éxito.'], 200);
        } else {
            return response()->json(['message' => 'Error al crear el nuevo registro.'], 500);
        }
    }
}
