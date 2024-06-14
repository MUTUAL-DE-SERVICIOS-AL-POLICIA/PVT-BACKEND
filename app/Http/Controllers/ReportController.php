<?php

namespace App\Http\Controllers;

use App\Exports\ArchivoPrimarioExport;
use App\Exports\EcoComMovementsExport;
use App\Models\Affiliate\Affiliate;
use App\Models\Contribution\ContributionType;
use App\Models\RetirementFund\RetirementFund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Exports\QualificationReportExport;

class ReportController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/report/report_affiliates_spouses",
     *      tags={"REPORTES"},
     *      summary="GENERA REPORTE DE AFILIADOS - CÓNYUGES",
     *      operationId="report_affiliates_spouses",
     *      description="Genera reporte de los afiliados y sus cónyuges",
     *      @OA\RequestBody(
     *          description= "Reporte de los afiliados y sus cónyuges",
     *          required=false,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="type", type="string",description="Extrensión de archivo", example=".xls"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(mediaType="multipart/form-data")
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * @param Request $request
     * @return void
     */
    public function report_affiliates_spouses(Request $request)
    {
        $date = date('Y-m-d');
        $list = Affiliate::leftjoin('spouses', 'spouses.affiliate_id', '=', 'affiliates.id')
            ->select(
                'affiliates.id as nup',
                'affiliates.identity_card as identity_card',
                'affiliates.first_name as first_name',
                'affiliates.second_name as second_name',
                'affiliates.last_name as last_name',
                'affiliates.mothers_last_name as mothers_last_name',
                'affiliates.surname_husband as surname_husband',
                'affiliates.date_entry as date_entry',
                'affiliates.birth_date as birth_date',
                'spouses.identity_card as spouse_identity_card',
                'spouses.first_name as spouse_first_name',
                'spouses.second_name as spouse_second_name',
                'spouses.last_name as spouse_last_name',
                'spouses.mothers_last_name as spouse_mothers_last_name',
                'spouses.surname_husband as spouse_surname_husband',
                DB::raw('DATE(MAX(spouses.created_at)) as spouse_registration_date'),
                'spouses.birth_date as spouse_birth_date',
                'affiliates.registration as registration'
            )
            ->groupBy(
                'affiliates.id',
                'affiliates.identity_card',
                'affiliates.first_name',
                'affiliates.second_name',
                'affiliates.last_name',
                'affiliates.mothers_last_name',
                'affiliates.surname_husband',
                'affiliates.date_entry',
                'affiliates.birth_date',
                'spouses.identity_card',
                'spouses.first_name',
                'spouses.second_name',
                'spouses.last_name',
                'spouses.mothers_last_name',
                'spouses.surname_husband',
                'spouses.birth_date',
                'affiliates.registration'
            )
            ->orderBy('affiliates.id', 'asc')
            ->get();

        $data_header = array(array(
            "NRO", "NUP", "CI TITULAR", "PRIMER NOMBRE", "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO",
            "AP. CASADA", "FECHA DE INGRESO", "FECHA DE NACIMIENTO", "CI VIUDA(O)", "PRIMER NOMBRE",
            "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO", "AP. CASADA", "FECHA REGISTRO VIUDA",
            "FECHA DE NACIMIENTO", "MATRÍCULA TITULAR"
        ));
        $i = 1;
        foreach ($list as $row) {
            array_push($data_header, array(
                $row->number = $i,
                $row->nup,
                $row->identity_card,
                $row->first_name,
                $row->second_name,
                $row->last_name,
                $row->mothers_last_name,
                $row->surname_husband,
                $row->date_entry,
                $row->birth_date,
                $row->spouse_identity_card,
                $row->spouse_first_name,
                $row->spouse_second_name,
                $row->spouse_last_name,
                $row->spouse_mothers_last_name,
                $row->spouse_surname_husband,
                $row->spouse_registration_date,
                $row->spouse_birth_date,
                $row->registration
            ));
            $i++;
        }
        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "reporte_afiliados_conyuges_" . $date;
        $type = $request->type;
        $extension = $type ?? '.xlsx';
        return Excel::download($export, $file_name . $extension);
    }

    /**
     * @OA\Post(
     *      path="/api/report/report_retirement_funds",
     *      tags={"REPORTES"},
     *      summary="GENERA REPORTE DE FONDO DE RETIRO",
     *      operationId="report_retirement_funds",
     *      description="Genera reporte de los trámites de fondo de retiro",
     *      @OA\RequestBody(
     *          description= "Reporte de trámites de fondo de retiro",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="start_date", type="date",description="Fecha inicio del reporte", example="2023-02-05"),
     *              @OA\Property(property="end_date", type="date",description="Fecha final del reporte", example="2023-02-14")
     *         ),
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
     * @param Request $request
     * @return void
     */

    public function report_retirement_funds(Request $request)
    {
        $date = date('Y-m-d');
        if ($request->start_date == NULL || $request->end_date == NULL) {
            $start_date = $date;
            $end_date = $date;
        } else {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
        }
        // 1. Obtener los tramites de FR con sus relaciones
        $list =  RetirementFund::select(
            'a.id as nup',
            'a.identity_card as identity_card',
            'a.first_name as first_name',
            'a.second_name as second_name',
            'a.last_name as last_name',
            'a.mothers_last_name as mothers_last_name',
            'a.surname_husband as surname_husband',
            'a.birth_date as birth_date',
            'a.date_death as date_death',
            'rf.code as code',
            'rf.reception_date as reception_date',
            'rfc.code as num_cert',
            'rfc.date as date_cert',
            'u.name as name_unit',
            'u.code as code_unit'
        )
        ->from('retirement_funds as rf')
        ->leftJoin('affiliates as a', 'rf.affiliate_id', '=', 'a.id')
        ->leftJoin('ret_fun_correlatives as rfc', function($join){
            $join->on('rf.id', '=','rfc.retirement_fund_id')
                ->where('rfc.wf_state_id',22)
                ->whereNull('rfc.deleted_at');
        })
        ->leftJoin(DB::raw('(SELECT DISTINCT ON (affiliate_id) affiliate_id, month_year, unit_id FROM contributions ORDER BY affiliate_id, month_year) as c'), 'c.affiliate_id', '=', 'a.id')
        ->leftJoin('units as u', 'u.id', '=', 'c.unit_id')
        ->whereBetween(DB::raw('DATE(rf.created_at)'), [$start_date, $end_date])
        ->whereIn('rf.wf_state_current_id', [22,23,24,26,47])
        ->whereNull('rfc.deleted_at')
        ->where('rf.code', 'not ilike', '%A')
        ->orderBy('a.id', 'asc')
        ->distinct('a.id')
        ->get();

        // 2. Obtener los resultados de los clasificadores
        $contributions = DB::table('contributions')
            ->whereIn('affiliate_id', $list->pluck('nup')->toArray()) // Utiliza los nup obtenidos de (1)
            ->orderBy('affiliate_id', 'asc')
            ->orderBy('month_year', 'asc')
            ->orderBy('contribution_type_id', 'asc')
            ->get();
        $resultContributions = [];
        foreach ($contributions as $contribution) {
            $afiliadoId = $contribution->affiliate_id;
            $contributionTypeId = $contribution->contribution_type_id;
            $currentDate = $contribution->month_year;
            if (!isset($resultContributions[$afiliadoId])) {
                // Si el afiliado no existe en el resultado de contribuciones
                $resultContributions[$afiliadoId] = [
                    'affiliate_id' => $afiliadoId,
                    'clasificadores' => []
                ];
                $currentClassifier = null;
            }
            if ($currentClassifier !== $contributionTypeId) {
                // Si cambió el contribution_type_id, guarda el mínimo y máximo del grupo anterior (si existe)
                if ($currentClassifier !== null) {
                    $resultContributions[$afiliadoId]['clasificadores'][] = [
                        'contribution_type_id' => $currentClassifier,
                        'min' => min($group),
                        'max' => max($group),
                    ];
                }
                // Inicia un nuevo grupo de contribution_type_id
                $currentClassifier = $contributionTypeId;
                $group = [$currentDate];
            } else {
                // Si no cambió el contribution_type_id, agrega la fecha al grupo actual
                $group[] = $currentDate;
            }
        }
        // Guarda el mínimo y máximo del último grupo para cada afiliado
        foreach ($resultContributions as &$afiliado) {
            $afiliado['clasificadores'][] = [
                'contribution_type_id' => $currentClassifier,
                'min' => min($group),
                'max' => max($group),
            ];
        }
        // Unir la información de (1) y (2)
        $finalResults = [];
        foreach ($list as $row) {
            $afiliadoId = $row->nup;
            if (isset($resultContributions[$afiliadoId])) {
                $finalResults[] = [
                    'nup' => $afiliadoId,
                    'identity_card' => $row->identity_card,
                    'first_name' => $row->first_name,
                    'second_name' => $row->second_name,
                    'last_name' => $row->last_name,
                    'mothers_last_name' => $row->mothers_last_name,
                    'surname_husband' => $row->surname_husband,
                    'birth_date' => $row->birth_date,
                    'date_death' => $row->date_death,
                    'code' => $row->code,
                    'reception_date' => $row->reception_date,
                    'num_cert' => $row->num_cert,
                    'date_cert' => $row->date_cert,
                    'name_unit' => $row->name_unit,
                    'code_unit' => $row->code_unit,
                    'clasificadores' => array_values($resultContributions[$afiliadoId]['clasificadores']),
                ];
            }
        }

        //Unificar encabezados
        $contribution_type_shortened= ContributionType::orderBy('id')->pluck('shortened')->toArray();

        $data_header = array(
            "NRO", "NUP", "CÉDULA DE IDENTIDAD", "PRIMER NOMBRE", "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO",
            "AP. CASADA", "FECHA NACIMIENTO", "FECHA FALLECIMIENTO", "NRO TRÁMITE", "FECHA RECEPCIÓN",
            "NRO CERTIFICACIÓN", "FECHA CERTIFICACIÓN", "UNIDAD INICIO DE FUNCIONES","CODIGO DE UNIDAD",
        );
        $data_header = array(array_merge($data_header, $contribution_type_shortened));

        //Unificar datos relacionados a cada encabezado
        $i = 1;
        foreach ($finalResults as &$row) {
            $classifiersByType = [];

            // Iterar sobre los diferentes tipos de contribution_type_id
            for ($tipo = 1; $tipo <= 14; $tipo++) {
                $classifiersByType[$tipo] = implode(PHP_EOL, array_map(function ($classifier) {
                    $classifier['min']= Carbon::createFromFormat('Y-m-d', $classifier['min'])->format('m/Y');
                    $classifier['max']= Carbon::createFromFormat('Y-m-d', $classifier['max'])->format('m/Y');
                    return "{$classifier['min']} - {$classifier['max']}";
                }, array_filter($row['clasificadores'], function ($classifier) use ($tipo) {
                    return $classifier['contribution_type_id'] == $tipo;
                })));
            }

            array_push($data_header, array(
                $i,
                $row['nup'],
                $row['identity_card'],
                $row['first_name'],
                $row['second_name'],
                $row['last_name'],
                $row['mothers_last_name'],
                $row['surname_husband'],
                $row['birth_date'],
                $row['date_death'],
                $row['code'],
                $row['reception_date'],
                $row['num_cert'],
                $row['date_cert'],
                $row['name_unit'],
                $row['code_unit'],
                $classifiersByType[1],
                $classifiersByType[2],
                $classifiersByType[3],
                $classifiersByType[4],
                $classifiersByType[5],
                $classifiersByType[6],
                $classifiersByType[7],
                $classifiersByType[8],
                $classifiersByType[9],
                $classifiersByType[10],
                $classifiersByType[11],
                $classifiersByType[12],
                $classifiersByType[13],
                $classifiersByType[14]
            ));

            $i++;
        }

        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "reporte_fondo_de_retiro_" . $date;
        $type = $request->type;
        $extension = $type ?? '.xls';
        return Excel::download($export, $file_name . $extension);

    }

    /**
     * @OA\Post(
     *      path="/api/report/report_payments_beneficiaries",
     *      tags={"REPORTES"},
     *      summary="GENERA REPORTE DE PAGOS Y DERECHOHABIENTES",
     *      operationId="report_payments_beneficiaries",
     *      description="Genera reporte de los pagos y derechohabientes de los beneficios",
     *      @OA\RequestBody(
     *          description= "Reporte de pagos y derechohabientes",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="start_date", type="date",description="Fecha inicio del reporte", example="2023-02-05"),
     *              @OA\Property(property="end_date", type="date",description="Fecha final del reporte", example="2023-02-14")
     *         ),
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
     * @param Request $request
     * @return void
     */
    public function report_payments_beneficiaries(Request $request)
    {
        $date = date('Y-m-d');

        if ($request->start_date == NULL || $request->end_date == NULL) {
            $start_date = $date;
            $end_date = $date;
        } else {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
        }

        $list = Affiliate::leftjoin('retirement_funds', 'retirement_funds.affiliate_id', '=', 'affiliates.id')
            ->leftJoin('cities', 'cities.id', '=', 'affiliates.city_identity_card_id')
            ->leftJoin('ret_fun_beneficiaries', 'ret_fun_beneficiaries.retirement_fund_id', '=', 'retirement_funds.id')
            ->leftJoin('kinships', 'kinships.id', '=', 'ret_fun_beneficiaries.kinship_id')
            ->leftJoin('procedure_modalities', 'procedure_modalities.id', '=', 'retirement_funds.procedure_modality_id')
            ->leftJoin('discount_type_retirement_fund', 'discount_type_retirement_fund.retirement_fund_id', '=', 'retirement_funds.id')
            ->leftJoin('discount_types', 'discount_types.id', '=', 'discount_type_retirement_fund.discount_type_id')
            ->leftJoin('ret_fun_correlatives', 'ret_fun_correlatives.retirement_fund_id', '=', 'retirement_funds.id')
            ->whereBetween(DB::raw('DATE(retirement_funds.created_at)'), [$start_date, $end_date])
            ->where('ret_fun_correlatives.wf_state_id', '=', 26)
            ->where('retirement_funds.ret_fun_state_id', '!=', 3)
            ->whereNull('ret_fun_correlatives.deleted_at')
            ->whereIn('discount_type_retirement_fund.discount_type_id', [1, 2, 3])
            ->select(
                'affiliates.id as nup',
                'retirement_funds.code as code',
                'procedure_modalities.name as procedure_modality',
                'affiliates.first_name as first_name',
                'affiliates.second_name as second_name',
                'affiliates.last_name as last_name',
                'affiliates.mothers_last_name as mothers_last_name',
                'affiliates.surname_husband as surname_husband',
                'affiliates.identity_card as identity_card',
                'cities.first_shortened as city',
                'retirement_funds.total_ret_fun as total_ret_fun',
                'retirement_funds.total_availability as total_availability',
                'ret_fun_correlatives.code as nro_res',
                'ret_fun_correlatives.date as date',
                DB::raw('MAX(CASE WHEN discount_types.id = 1 THEN discount_type_retirement_fund.amount END) AS advance_ret_fun'),
                DB::raw('MAX(CASE WHEN discount_types.id = 2 THEN discount_type_retirement_fund.amount END) AS loan_retention'),
                DB::raw('MAX(CASE WHEN discount_types.id = 3 THEN discount_type_retirement_fund.amount END) AS guarantor_retention'),
                DB::raw('concat_full_name(ret_fun_beneficiaries.first_name, ret_fun_beneficiaries.second_name, ret_fun_beneficiaries.last_name, ret_fun_beneficiaries.mothers_last_name, ret_fun_beneficiaries.surname_husband) AS beneficiary_full_name'),
                'kinships.name as kinship',
                'ret_fun_beneficiaries.amount_ret_fun as amount_ret_fun'
            )
            ->groupBy(
                'affiliates.id',
                'retirement_funds.code',
                'procedure_modalities.name',
                'affiliates.first_name',
                'affiliates.second_name',
                'affiliates.last_name',
                'affiliates.mothers_last_name',
                'affiliates.surname_husband',
                'affiliates.identity_card',
                'cities.first_shortened',
                'retirement_funds.total_ret_fun',
                'retirement_funds.total_availability',
                'ret_fun_correlatives.code',
                'ret_fun_correlatives.date',
                'kinships.name',
                'ret_fun_beneficiaries.amount_ret_fun',
                'ret_fun_beneficiaries.first_name',
                'ret_fun_beneficiaries.second_name',
                'ret_fun_beneficiaries.last_name',
                'ret_fun_beneficiaries.mothers_last_name',
                'ret_fun_beneficiaries.surname_husband'
            )
            ->orderBy('affiliates.id')
            ->get();

        $dataHeader = array(
            [
                "NRO", "NUP", "NRO TRÁMITE", "MODALIDAD", "PRIMER NOMBRE", "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO",
                "AP. CASADA", "CÉDULA DE IDENTIDAD", "EXPEDICIÓN", "TOTAL FONDO DE RETIRO", "TOTAL DISPONIBILIDAD",
                "NRO RESOLUCIÓN", "FECHA RESOLUCIÓN", "ANTICIPO FONDO DE RETIRO", "RETENCIÓN PAGO PRÉSTAMO",
                "RETENCIÓN GARANTES", "TITULAR/BENEFICIARIO", "PARENTESCO", "MONTO PARA BENEFICIARIOS"
            ]
        );

        $i = 1;
        foreach ($list as $row) {
            $dataHeader[] = [
                $i,
                $row->nup,
                $row->code,
                $row->procedure_modality,
                $row->first_name,
                $row->second_name,
                $row->last_name,
                $row->mothers_last_name,
                $row->surname_husband,
                $row->identity_card,
                $row->city,
                $row->total_ret_fun,
                $row->total_availability,
                $row->nro_res,
                $row->date,
                $row->advance_ret_fun,
                $row->loan_retention,
                $row->guarantor_retention,
                $row->beneficiary_full_name,
                $row->kinship,
                $row->amount_ret_fun
            ];
            $i++;
        }
        $export = new ArchivoPrimarioExport($dataHeader);
        $fileName = "reporte_pagos_derechohabientes_" . $date;
        $type = $request->type;
        $extension = $type ?? '.xls';
        return Excel::download($export, $fileName . $extension);
    }

    public function download_qualification_report(request $request)
    {
        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $query = "SELECT
                    CONCAT(ase2.first_name,
                        CASE WHEN TRIM(ase2.second_name) <> '' THEN CONCAT(' ', ase2.second_name) ELSE '' END,
                        ' ',
                        ase2.second_last_name,
                        ' ',
                        ase2.last_name) as full_name, asq.description as question, asa2.description as answer, TO_CHAR(ase.created_at, 'YYYY-MM-DD HH24:MI:SS') as formatted_created_at
                FROM api_survey_answer asa
                JOIN api_survey_question asq ON asa.question_id = asq.id
                JOIN api_survey_answeroption asa2 ON asa.answer_option_id = asa2.id
                JOIN api_survey_evaluation ase ON asa.evaluation_id = ase.id
                JOIN api_survey_employee ase2 ON ase.employee_id = ase2.id
                WHERE ase.created_at BETWEEN :start_date AND :end_date
                GROUP BY full_name, question, answer, formatted_created_at;";
        $bindings = [
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
        $data = collect(DB::connection('db_survey')->select($query, $bindings));
        return Excel::download(new QualificationReportExport($data), 'qualification_report.xls');
    }
    public function report_overpayments(){
        $subquery = DB::table('eco_com_movements as e1')
                    ->select('e1.affiliate_id',
                            'e1.description',
                            DB::raw("CONCAT(a.first_name, ' ', COALESCE(a.second_name, ''), ' ', a.last_name, ' ', a.mothers_last_name) as affiliate_name"),
                            'a.identity_card',
                            'e1.amount',
                            'e1.balance')
                    ->join('affiliates as a', 'e1.affiliate_id', '=', 'a.id')
                    ->whereRaw('e1.id = (
                        SELECT MAX(e2.id)
                        FROM eco_com_movements as e2
                        WHERE e2.affiliate_id = e1.affiliate_id
                    )');

        $data= DB::table(DB::raw("({$subquery->toSql()}) as sub"))
            ->mergeBindings($subquery)
            ->orderBy('affiliate_id')
            ->get();
        return Excel::download(new EcoComMovementsExport($data), 'eco_com_movements_report.xls');
    }
}
