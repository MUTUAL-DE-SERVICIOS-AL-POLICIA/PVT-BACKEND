<?php

namespace App\Http\Controllers\Contribution;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\Degree;
use App\Models\Contribution\ContributionPassive;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;

class ContributionPassiveController extends Controller
{
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
     * @OA\Post(
     *      path="/api/contribution/passive_affiliate_contribution",
     *      tags={"CONTRIBUCION"},
     *      summary="CONTRIBUCIONES DEL AFILIADO - SECTOR PASIVO",
     *      operationId="getContributionsPassive",
     *      description="contribuciones del afiliado - sector pasivo",
     *      @OA\RequestBody(
     *          description= "affiliate_id",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="affiliate_id", type="integer",description="affiliate_id",example=33)
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
     *
     * @param Request $request
     * @return void
     */

    public function show(Request $request)
    {
        $request->validate([
            'affiliate_id' => 'required|integer|exists:contribution_passives,affiliate_id'
        ]);

        $affiliate = Affiliate::find($request->affiliate_id);
        $year_min = $this->get_minimum_year($request->affiliate_id);
        $year_max = $this->get_maximum_year($request->affiliate_id);

        $all_contributions = collect();
        $months = DB::table('months')->get();

        for ($i = $year_max; $i >= $year_min; $i--) {
            $contributions = collect();
            $contribution_passives = ContributionPassive::whereAffiliateId($request->affiliate_id)
                ->whereYear('month_year', $i)
                ->orderBy('month_year', 'asc')
                ->get();

            foreach ($months as $month) {
                $mes = (string)$month->id;
                $detail = collect();
                foreach ($contribution_passives as $contributions_passive) {
                    $m = ltrim(Carbon::parse($contributions_passive->month_year)->format('m'), "0");
                    if ($m == $mes) {
                        $detail->push(
                            $contributions_passive
                        );
                    }
                }
                $contributions->push([
                    'month' => $month->name,
                    'detail' => (object)$detail->first()
                ]);
            }
            $all_contributions->push([
                'year' => (string)$i,
                'contributions' => $contributions
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
                'all_contributions' => $all_contributions
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/contribution/search_passive_affiliate_contribution",
     *     tags={"CONTRIBUCION"},
     *     summary="Filtrado y listado de contribuciones - Sector Pasivo",
     *     operationId="getContributionPassive",
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
     * Get list of contributions passive.
     *
     * @param Request $request
     * @return void
     */

    public function SearchContributionPassive(Request $request)
    {

        $request->validate([
            'affiliate_id' => 'required|integer|exists:contribution_passives,affiliate_id',
        ]);
        $year = request('year') ?? '';
        $month = request('month') ?? '';
        $contributionable_type = request('contributionable_type') ?? '';
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
        if ($contributionable_type != '') {
            array_push($conditions, array('contributionable_type', 'like', "%{$contributionable_type}%"));
        }
        $per_page = $request->per_page ?? 10;
        $contributions_passives = ContributionPassive::whereAffiliateId($request->affiliate_id)->where($conditions)->orderBy('month_year', $order_year)->paginate($per_page);

        foreach ($contributions_passives as $contributions_passive) {
            $year = Carbon::parse($contributions_passive->month_year)->format('Y');
            $month = Carbon::parse($contributions_passive->month_year)->format('m');
            if ($contributions_passive->contributionable_type == "discount_type_economic_complement") {
                $contributions_passive->contributionable_type_name = "Complemento Economico";
            } else {
                if ($contributions_passive->contributionable_type == "payroll_senasirs") {
                    $contributions_passive->contributionable_type_name = "Senasir";
                } else {
                    $contributions_passive->contributionable_type_name = "";
                }
            }
            $contributions_passive->year = $year;
            $contributions_passive->month = $month;
        }
        return $contributions_passives;
    }

    public function get_minimum_year($id)
    {
        $data = DB::table('contribution_passives')->where('affiliate_id', $id)->min('month_year');
        $min = Carbon::parse($data)->format('Y');

        return $min;
    }

    public function get_maximum_year($id)
    {
        $data = DB::table('contribution_passives')->where('affiliate_id', $id)->max('month_year');
        $max = Carbon::parse($data)->format('Y');

        return $max;
    }

    public function printCertificationContributionPassive(Request $request, $affiliate_id)
    {
        $request['affiliate_id'] = $affiliate_id;
        $request->validate([
            'affiliate_id' => 'required|integer|exists:contribution_passives,affiliate_id',
        ]);

        $affiliate = Affiliate::find($affiliate_id);
        $user = Auth::user();
        $degree = Degree::find($affiliate->degree_id);
        $contributions = collect();
        $value = false;
        if ($affiliate->dead) {
            if ($affiliate->spouse->dead != null || $affiliate->spouse->dead) {
                $contributions_passives = ContributionPassive::whereAffiliateId($affiliate_id)
                    ->orderBy('month_year', 'asc')
                    ->get();
            } else {
                $contributions_passives = ContributionPassive::whereAffiliateId($affiliate_id)
                    ->where('affiliate_rent_class', 'VIUDEDAD')
                    ->orderBy('month_year', 'asc')
                    ->get();
                $value = true;
            }
        } else {
            $contributions_passives = ContributionPassive::whereAffiliateId($affiliate_id)
                ->orderBy('month_year', 'asc')
                ->get();
        }

        foreach ($contributions_passives as $contributions_passive) {
            $year = Carbon::parse($contributions_passive->month_year)->format('Y');
            $month = Carbon::parse($contributions_passive->month_year)->format('m');
            if ($contributions_passive->affiliate_rent_class == 'VEJEZ') {
                $rent_class = 'Titular';
            } else {
                $rent_class = 'Viuda';
            }
            if ($contributions_passive->contributionable_type == 'discount_type_economic_complement') {
                $modality = $contributions_passive->contributionable->economic_complement->eco_com_procedure;
                $modality_year = Carbon::parse($modality->year)->format('Y');
                $text = "C.E." . $modality->semester . " Semestre " . $modality_year;
            } else {
                $text = $contributions_passive->contributionable_type == 'payroll_senasirs' ? 'Tipo de descuento Senasir' : 'Tipo de descuento No Especificado';
            }
            $contributions->push([
                'id' => $contributions_passive->id,
                'month_year' => $contributions_passive->month_year,
                'month' => $month,
                'year' => $year,
                'rent_class' => $rent_class,
                'description' => $text,
                'quotable' => $contributions_passive->quotable,
                'total' => $contributions_passive->total,
                'type' => $contributions_passive->contributionable_type
            ]);
        }
        $num = 0;
        $data = [
            'header' => [
                'direction' => 'DIRECCIÓN DE BENEFICIOS ECONÓMICOS',
                'unity' => 'UNIDAD DE OTORGACIÓN DE FONDO DE RETIRO
                            POLICIAL, CUOTA MORTUORIA Y AUXILIO MORTUORIO',
                'table' => [
                    ['Usuario', $user->username],
                    ['Fecha', Carbon::now()->format('d-m-Y')],
                    ['Hora', Carbon::now()->format('H:i:s')],
                ]
            ],
            'num' => $num,
            'degree' => $degree,
            'affiliate' => $affiliate,
            'user' => $user,
            'value' => $value,
            'contributions' => $contributions
        ];
        $pdf = PDF::loadView('contribution.print.certification_contribution_eco_com', $data);
        return $pdf->stream('contributions_p.pdf');
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
