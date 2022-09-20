<?php

namespace App\Http\Controllers\Contribution;

use App\Helpers\Util;
use App\Http\Controllers\Controller;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\Degree;
use App\Models\Contribution\Contribution;
use App\Models\Contribution\ContributionPassive;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppContributionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function all_contributions(Request $request, $id, $year)
    {
        $request['affiliate_id'] = $id;
        $request['year'] = $year;
        $request->validate([
            'affiliate_id' => 'required|integer|exists:affiliates,id',
            'year' => 'integer'
        ]);
        
        $affiliate = Affiliate::find($id);
        $degree = Degree::find($affiliate->degree_id);
        $contributions = collect();
        $contributions_passives = ContributionPassive::whereAffiliateId($id)
            ->whereYear('month_year', $year)
            ->orderBy('month_year', 'asc')
            ->get();
        foreach ($contributions_passives as $contributions_passive) {
            $modality = $contributions_passive->contributionable->economic_complement->eco_com_procedure;
            $modality_year = Carbon::parse($modality->year)->format('Y');
            $text = "C.E." . $modality->semester . " Semestre " . $modality_year;
            $contributions->push([
                'state' => 'PASIVO',
                'id' => $contributions_passive->id,
                'month_year' => $contributions_passive->month_year,
                'description' => $text,
                'quotable' => $contributions_passive->quotable,
                'retirement_fund' => null,
                'mortuary_quota' => null,
                'total' => $contributions_passive->total,
                'type' => $contributions_passive->contributionable_type
            ]);
        }

        $contributions_actives = Contribution::whereAffiliateId($id)
            ->whereYear('month_year', $year)
            ->orderBy('month_year', 'asc')
            ->get();
        foreach ($contributions_actives as $contributions_active) {
            $contributions->push([
                'state' => 'ACTIVO',
                'id' => $contributions_active->id,
                'month_year' => $contributions_active->month_year,
                'description' => null,
                'quotable' => $contributions_active->quotable,
                'retirement_fund' => $contributions_active->retirement_fund,
                'mortuary_quota' => $contributions_active->mortuary_quota,
                'total' => $contributions_active->total,
                'type' => $contributions_active->contributionable_type
            ]);
        }
        $year_min = $this->get_minimum_year($id);
        $year_max = $this->get_maximum_year($id);

        return response()->json([
            'message' => 'Contribuciones del Afiliado',
            'payload' => [
                'degree' => $degree->name,
                'first_name' => $affiliate->first_name,
                'second_name' => $affiliate->second_name,
                'last_name' => $affiliate->last_name,
                'mothers_last_name' => $affiliate->mothers_last_name,
                'surname_husband' => $affiliate->surname_husband,
                'identity_card' => $affiliate->identity_card,
                'city_identity_card' => $affiliate->city_identity_card->first_shortened,
                'year_min' => $year_min,
                'year_max' => $year_max,
                'contributions' => $contributions
            ],
        ]);
    }

    public function get_minimum_year($id)
    {
        $data = DB::table('contributions')->where('affiliate_id', $id)->min('month_year');
        $min = Carbon::parse($data)->format('Y');

        return $min;
    }

    public function get_maximum_year($id)
    {
        $data1 = DB::table('contribution_passives')->where('affiliate_id', $id)->max('month_year');
        $max1 = Carbon::parse($data1)->format('Y');

        $data2 = DB::table('contributions')->where('affiliate_id', $id)->max('month_year');
        $max2 = Carbon::parse($data2)->format('Y');

        if ($max1 > $max2)
            return $max1;
        return $max2;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}