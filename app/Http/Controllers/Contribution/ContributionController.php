<?php

namespace App\Http\Controllers\Contribution;

use App\Helpers\Util;
use App\Http\Controllers\Controller;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\Breakdown;
use App\Models\Affiliate\Degree;
use App\Models\Contribution\Contribution;
use App\Models\Contribution\Reimbursement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Auth;
use Illuminate\Support\Facades\App;

class ContributionController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/contribution/search_active_affiliate_contribution",
     *     tags={"CONTRIBUCION"},
     *     summary="Filtrado y listado de contribuciones - Sector Activo",
     *     operationId="getContributionActive",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Página a mostrar",
     *         example=1,
     *         required=false, 
     *       ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Por Página",
     *         example=10,
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="sortDesc",
     *         in="query",
     *         description="Vector de orden descendente(0) o ascendente(1)",
     *         example=1,
     *         required=false,
     *     ),
     *    @OA\Parameter(
     *         name="affiliate_id",
     *         in="query",
     *         description="Id del Afiliado",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filtro por Año",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filtro por Mes",
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get list of contributions active
     *
     * @param Request $request
     * @return void
     */

    public function SearchContributionActive(Request $request)
    {
        $request->validate([
            'affiliate_id' => 'required|integer|exists:contributions,affiliate_id',
        ]);

        $year = request('year') ?? '';
        $month = request('month') ?? '';
        $breakdown = request('breakdown') ?? '';

        $order = request('sortDesc') ?? '';
        if ($order != '') {
            if ($order) {
                $order_year = 'asc';
            }
            if (!$order) {
                $order_year = 'desc';
            }
        } else {
            $order_year = 'desc';
        }

        $conditions = [];
        if ($year != '') {
            array_push($conditions, array('month_year', 'like', "%{$year}%-%"));
        }
        if ($month != '') {
            array_push($conditions, array('month_year', 'like', "%-%{$month}%-%"));
        }
        if ($breakdown != '') {
            array_push($conditions, array('breakdowns.name', 'ilike', "%{$breakdown}%"));
        }
        
        $per_page = $request->per_page ?? 10;

        $affiliate = Affiliate::find($request->affiliate_id);

        $reimbursements = $affiliate->reimbursements()->selectRaw(
            "
            affiliate_id,
            month_year,
            extract(month from month_year) as month,
            extract(year from month_year) as year,
            null,
            null,
            base_wage,
            seniority_bonus,
            study_bonus,
            position_bonus,
            border_bonus,
            east_bonus,
            public_security_bonus,
            gain,
            quotable,
            retirement_fund,
            mortuary_quota,
            total,
            null,
            'RE' as title,
            type,
            breakdowns.id as breakdown_id,
            breakdowns.name as breakdown_name"
        )->join("breakdowns", "breakdowns.id", "=", "reimbursements.breakdown_id")
            ->where($conditions)
            ->orderBy('month_year', $order_year);

        return $contributions = $affiliate->contributions()->selectRaw(
            "
            affiliate_id,
            month_year,
            extract(month from month_year) as month,
            extract(year from month_year) as year,
            degree_id,
            unit_id,
            base_wage,
            seniority_bonus,
            study_bonus,
            position_bonus,
            border_bonus,
            east_bonus,
            public_security_bonus,
            gain,
            quotable,
            retirement_fund,
            mortuary_quota,
            total,
            breakdown_id,
            'C' as title,
            type,
            breakdowns.id as breakdown_id,
            breakdowns.name as breakdown_name"
        )->join("breakdowns", "breakdowns.id", "=", "contributions.breakdown_id")
            ->union($reimbursements)
            ->where($conditions)
            ->orderBy('month_year', $order_year)
            ->paginate($per_page);
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
     * @OA\Post(
     *      path="/api/contribution/active_affiliate_contribution",
     *      tags={"CONTRIBUCION"},
     *      summary="CONTRIBUCIONES DEL AFILIADO - SECTOR ACTIVO",
     *      operationId="getContributions",
     *      description="contribuciones del afiliado",
     *      @OA\RequestBody(
     *          description= "affiliate_id",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="affiliate_id", type="integer",description="affiliate_id",example=123)
     *            )
     *          ),
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *            type="object"
     *         )
     *      )
     * )
     *
     * Logs user into the system.
     *
     * @param Request $request
     * @return void
     */

    public function show(Request $request)
    {
        $request->validate([
            'affiliate_id' => 'required|integer|exists:affiliates,id'
        ]);

        $affiliate = Affiliate::find($request->affiliate_id);
        $year_min = $this->get_minimum_year($request->affiliate_id);
        $year_max = $this->get_maximum_year($request->affiliate_id);
        $contribution_total = 0;

        $all_contributions = collect();

        $reimbursements = Reimbursement::whereAffiliateId($request->affiliate_id)
            ->orderBy('month_year', 'desc')
            ->get();
        $months = DB::table('months')->get();
        for ($i = $year_max; $i >= $year_min; $i--) {
            $full_total = 0;

            $contributions = collect();

            $contributions_actives = Contribution::whereAffiliateId($request->affiliate_id)
                ->whereYear('month_year', $i)
                ->get();

            foreach ($months as $month) {
                $mes = (string)$month->id;
                $detail = collect();
                foreach ($contributions_actives as $contributions_active) {
                    $contribution_total = $contributions_active->total;
                    $reimbursement_total = 0;
                    $full_total = $contributions_active->total;

                    foreach ($reimbursements as $reimbursement) {
                        if ($contributions_active->month_year == $reimbursement->month_year) {
                            $reimbursement_total = $reimbursement->total;
                            $full_total = $contribution_total + $reimbursement_total;
                        }
                    }
                    $m = ltrim(Carbon::parse($contributions_active->month_year)->format('m'), "0");

                    if ($m == $mes) {
                        $detail->push([
                            'id' => $contributions_active->id,
                            'month_year' => $contributions_active->month_year,
                            'quotable' => Util::money_format($contributions_active->quotable),
                            'retirement_fund' => Util::money_format($contributions_active->retirement_fund),
                            'mortuary_quota' => Util::money_format($contributions_active->mortuary_quota),
                            'reimbursement_total' => Util::money_format($reimbursement_total),
                            'total' => Util::money_format($contribution_total),
                            'contribution_total' => Util::money_format($full_total),
                            'type' => $contributions_active->contributionable_type
                        ]);
                    }
                }
                $contributions->push([
                    'month' => $month->name,
                    'detail' => (object)$detail->first()
                ]);
            }
            $all_contributions->push([
                'year' => $i . "",
                'contributions' => $contributions,
            ]);
        }

        return response()->json([
            'payload' => [
                'first_name' => $affiliate->first_name,
                'second_name' => $affiliate->second_name,
                'last_name' => $affiliate->last_name,
                'mothers_last_name' => $affiliate->mothers_last_name,
                'surname_husband' => $affiliate->surname_husband,
                'identity_card' => $affiliate->identity_card,
                'city_identity_card' => $affiliate->city_identity_card->first_shortened ?? '',
                'all_contributions' => $all_contributions
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
        $data2 = DB::table('contributions')->where('affiliate_id', $id)->max('month_year');
        $max2 = Carbon::parse($data2)->format('Y');
        return $max2;
    }

    public function printCertificationContributionActive(Request $request, $affiliate_id)
    {
        $request['affiliate_id'] = $affiliate_id;
        $request->validate([
            'affiliate_id' => 'required|integer|exists:contributions,affiliate_id',
        ]);

        $affiliate = Affiliate::find($affiliate_id);
        $user = Auth::user();
        $degree = Degree::find($affiliate->degree_id);
        $contributions = Contribution::whereAffiliateId($affiliate_id)
            ->where('total', '>', 0)
            ->orderBy('month_year', 'asc')
            ->get();
        $reimbursements = Reimbursement::whereAffiliateId($affiliate_id)
            ->orderBy('month_year', 'asc')
            ->get();
        $num = 0;
        $data = [
            'header' => [
                'direction' => 'DIRECCIÓN DE BENEFICIOS ECONÓMICOS',
                'unity' => 'UNIDAD DE OTORGACIÓN DE FONDO DE RETIRO
                            POLICIAL, CUOTA MORTUORIA Y AUXILIO MORTUORIO',
                'table' => [
                    ['Usuario', $user->username],
                    ['Fecha', Carbon::now('GMT-4')->format('d-m-Y')],
                    ['Hora', Carbon::now('GMT-4')->format('H:i:s')],
                ]
            ],
            'num' => $num,
            'degree' => $degree,
            'affiliate' => $affiliate,
            'user' => $user,
            'contributions' => $contributions,
            'reimbursements' => $reimbursements
        ];

        $pdf = PDF::loadView('contribution.print.certification_contribution_active', $data);

        return $pdf->stream('contributions_a.pdf');
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
     * Update the specified resource in storage.co
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
