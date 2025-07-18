<?php

namespace App\Http\Controllers;

use App\Exports\AffiliatesSpousesExport;
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
     * @OA\Get(
     *      path="/api/report/report_affiliates_spouses",
     *      tags={"REPORTES"},
     *      summary="GENERA REPORTE DE AFILIADOS - CÓNYUGES",
     *      operationId="report_affiliates_spouses",
     *      description="Genera reporte de los afiliados y sus cónyuges",
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
        $list = DB::table('affiliates')
            ->select(
                'affiliates.id as nup',
                'affiliates.identity_card',
                'affiliates.first_name',
                'affiliates.second_name',
                'affiliates.last_name',
                'affiliates.mothers_last_name',
                'affiliates.surname_husband',
                'affiliates.date_entry',
                'affiliates.birth_date',
                'spouses.identity_card as spouse_identity_card',
                'spouses.first_name as spouse_first_name',
                'spouses.second_name as spouse_second_name',
                'spouses.last_name as spouse_last_name',
                'spouses.mothers_last_name as spouse_mothers_last_name',
                'spouses.surname_husband as spouse_surname_husband',
                'spouses.created_at as spouse_create_date',
                'spouses.birth_date as spouse_birth_date',
                'affiliates.registration as registration',
                'spouses.registration as registration_spouse'
            )
            ->leftJoin('spouses', 'spouses.affiliate_id', '=', 'affiliates.id')
            ->orderBy('affiliates.id', 'asc');

        return Excel::download(new AffiliatesSpousesExport($list), 'affiliates_spouses_report.xlsx');
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
            $start_date = Carbon::parse($request->start_date)->toDateString(); // Devuelve 'YYYY-MM-DD'
            $end_date = Carbon::parse($request->end_date)->toDateString();
        }
        $fn_reporte_contribuciones = DB::select(
            "SELECT * FROM fn_report_fr_contributions_resumen(?, ?);",
            [$start_date, $end_date]
        );
        
        $data_header = array([
            "NRO",
            "NUP",
            "CÉDULA DE IDENTIDAD",
            "PRIMER NOMBRE",
            "SEGUNDO NOMBRE",
            "AP. PATERNO",
            "AP. MATERNO",
            "AP. CASADA",
            "FECHA NACIMIENTO",
            "FECHA FALLECIMIENTO",
            "NRO TRÁMITE",
            "FECHA RECEPCIÓN",
            "NRO CERTIFICACIÓN",
            "FECHA CERTIFICACIÓN",
            "UNIDAD INICIO DE FUNCIONES",
            "CODIGO DE UNIDAD",
            "1 SERV ACTIVO",
            "2 PER. ITEM 0 CON APORTE",
            "3 PER. ITEM 0 SIN APORTE",
            "4 PER. BSF CON APORTE",
            "5 PER. BSF SIN APORTE",
            "6 PER. ANTERIORES A MAYO DE 1976 SIN APORTE",
            "7 PER. CERT. CON APORTE",
            "8 PER. CERT. SIN APORTE",
            "9 PER. NO TRABAJADO",
            "10 DISPONIBILIDAD",
            "11 PERIODO PAGADO CON ANTERIORIDAD",
            "12 DISP. CON APORTE",
            "13 DISP. SIN APORTE",
            "14 INEXISTENCIA DE PLAN. DE HAB.",
            //"15 DEVOLUCION",
            "FECHA DERIVACIÓN A CI",
            "FECHA VALIDACIÓN"
        ]);
        
        $i = 1;
        foreach ($fn_reporte_contribuciones as $row) {
            $data_header[] = [
                $i,
                $row->id,
                $row->identity_card,
                $row->first_name,
                $row->second_name,
                $row->last_name,
                $row->mothers_last_name,
                $row->surname_husband,
                $row->birth_date,
                $row->date_death,
                $row->code,
                $row->reception_date,
                $row->num_cert,
                $row->date_cert,
                $row->name_unit,
                $row->code_unit,
                $row->{'1_Servicio_Activo'},
                $row->{'2_Periodo_en_item_0_Con_Aporte'},
                $row->{'3_Periodo_en_item_0_Sin_Aporte'},
                $row->{'4_Periodo_de_Batallon_de_Seguridad_Fisica_Con_Aporte'},
                $row->{'5_Periodo_de_Batallon_de_Seguridad_Fisica_Sin_Aporte'},
                $row->{'6_Periodos_anteriores_a_Mayo_de_1976_Sin_Aporte'},
                $row->{'7_Periodo_Certificacion_Con_Aporte'},
                $row->{'8_Periodo_Certificacion_Sin_Aporte'},
                $row->{'9_Periodo_no_Trabajado'},
                $row->{'10_Disponibilidad'},
                $row->{'11_Periodo_pagado_con_anterioridad'},
                $row->{'12_Disponibilidad_Con_Aporte'},
                $row->{'13_Disponibilidad_Sin_Aporte'},
                $row->{'14_Inexistencia_de_Planilla_de_Haberes'},
                //$row->{'15_Devolucion'},
                $row->fec_derivacion,
                $row->fec_validacion,
            ];
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
     *              @OA\Property(property="end_date", type="date",description="Fecha final del reporte", example="2023-02-14"),
     *              @OA\Property(property="type", type="string",description="Tipo de reporte", example="Cuota Mortuoria")
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
        ini_set('max_execution_time', '300000');
        $date = date('Y-m-d');
        $data_header = [];

        if ($request->start_date == NULL || $request->end_date == NULL) {
            $start_date = $date;
            $end_date = $date;
        } else {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
        }

        switch($request->type) {
            case 'Fondo de Retiro Policial':
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

                $data_header = array(
                    [
                        "NRO", "NUP", "NRO TRÁMITE", "MODALIDAD", "PRIMER NOMBRE", "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO",
                        "AP. CASADA", "CÉDULA DE IDENTIDAD", "EXPEDICIÓN", "TOTAL FONDO DE RETIRO", "TOTAL DISPONIBILIDAD",
                        "NRO RESOLUCIÓN", "FECHA RESOLUCIÓN", "ANTICIPO FONDO DE RETIRO", "RETENCIÓN PAGO PRÉSTAMO",
                        "RETENCIÓN GARANTES", "TITULAR/BENEFICIARIO", "PARENTESCO", "MONTO PARA BENEFICIARIOS"
                    ]
                );

                $i = 1;
                foreach ($list as $row) {
                    $data_header[] = [
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
                break;
            case 'Cuota Mortuoria':
                $data = Affiliate::leftjoin('quota_aid_mortuaries as qam', 'qam.affiliate_id', '=', 'affiliates.id')
                        ->leftjoin('procedure_modalities as pm', 'pm.id', '=', 'qam.procedure_modality_id')
                        ->leftjoin('quota_aid_beneficiaries as qab', 'qab.quota_aid_mortuary_id', '=', 'qam.id')
                        ->leftjoin('kinships as k', 'k.id', '=', 'qab.kinship_id')
                        ->whereBetween(DB::raw('DATE(qam.created_at)'), [$start_date, $end_date])
                        ->whereNull('qam.deleted_at')
                        ->whereIn('qam.procedure_modality_id', [8, 9])
                        ->where('qam.code', 'not ilike', '%A')
                        ->select('affiliates.identity_card','affiliates.first_name', 'affiliates.second_name', 'affiliates.last_name', 'affiliates.mothers_last_name', 'qam.code', 'pm.name',
                                 'qab.first_name as first_name_beneficiary', 'qab.second_name as second_name_beneficiary', 'qab.last_name as last_name_beneficiary', 'qab.mothers_last_name as mothers_last_name_beneficiary',
                                 'qab.identity_card as identity_card_beneficiary', 'k.name as kinship', 'qab.paid_amount')
                        ->groupBy('affiliates.identity_card','affiliates.first_name', 'affiliates.second_name', 'affiliates.last_name', 'affiliates.mothers_last_name', 'qam.code', 'pm.name',
                                 'first_name_beneficiary', 'second_name_beneficiary', 'last_name_beneficiary', 'mothers_last_name_beneficiary', 'identity_card_beneficiary', 'kinship', 'qab.paid_amount')
                        ->orderBy('affiliates.identity_card')
                        ->get();
                $data_header = array(
                    [
                        "C.I. TITULAR", "PRIMER NOMBRE TITULAR", "SEGUNDO NOMBRE TITULAR", "AP. PATERNO TITULAR", "AP. MATERNO TITULAR",
                        "TRÁMITE", "MODALIDAD", "BENEFICIARIO PRIMER NOMBRE", "BENEFICIARIO SEGUNDO NOMBRE", "BENEFICIARIO AP. PATERNO",
                        "BENEFICIARIO AP.MATERNO", "BENEFICIARIO C.I.", "PARENTESCO", "MONTO"
                    ]
                );
                foreach($data as $row) {
                    $data_header[] = [
                        $row->identity_card,
                        $row->first_name,
                        $row->second_name,
                        $row->last_name,
                        $row->mothers_last_name,
                        $row->code,
                        $row->name,
                        $row->first_name_beneficiary,
                        $row->second_name_beneficiary,
                        $row->last_name_beneficiary,
                        $row->mothers_last_name_beneficiary,
                        $row->identity_card_beneficiary,
                        $row->kinship,
                        $row->paid_amount
                    ];
                }
                break;
            case 'Auxilio Mortuorio':
                $data = Affiliate::leftjoin('quota_aid_mortuaries as qam', 'qam.affiliate_id', '=', 'affiliates.id')
                        ->leftjoin('procedure_modalities as pm', 'pm.id', '=', 'qam.procedure_modality_id')
                        ->leftjoin('quota_aid_beneficiaries as qab', 'qab.quota_aid_mortuary_id', '=', 'qam.id')
                        ->leftjoin('kinships as k', 'k.id', '=', 'qab.kinship_id')
                        ->whereBetween(DB::raw('DATE(qam.created_at)'), [$start_date, $end_date])
                        ->whereNull('qam.deleted_at')
                        ->whereIn('qam.procedure_modality_id', [13,14,15])
                        ->where('qam.code', 'not ilike', '%A')
                        ->select('affiliates.identity_card','affiliates.first_name', 'affiliates.second_name', 'affiliates.last_name', 'affiliates.mothers_last_name', 'qam.code', 'pm.name',
                                 'qab.first_name as first_name_beneficiary', 'qab.second_name as second_name_beneficiary', 'qab.last_name as last_name_beneficiary', 'qab.mothers_last_name as mothers_last_name_beneficiary',
                                 'qab.identity_card as identity_card_beneficiary', 'k.name as kinship', 'qab.paid_amount')
                        ->groupBy('affiliates.identity_card','affiliates.first_name', 'affiliates.second_name', 'affiliates.last_name', 'affiliates.mothers_last_name', 'qam.code', 'pm.name',
                                 'first_name_beneficiary', 'second_name_beneficiary', 'last_name_beneficiary', 'mothers_last_name_beneficiary', 'identity_card_beneficiary', 'kinship', 'qab.paid_amount')
                        ->orderBy('affiliates.identity_card')
                        ->get();
                $data_header = array(
                    [
                        "C.I. TITULAR", "PRIMER NOMBRE TITULAR", "SEGUNDO NOMBRE TITULAR", "AP. PATERNO TITULAR", "AP. MATERNO TITULAR",
                        "TRÁMITE", "MODALIDAD", "BENEFICIARIO PRIMER NOMBRE", "BENEFICIARIO SEGUNDO NOMBRE", "BENEFICIARIO AP. PATERNO",
                        "BENEFICIARIO AP.MATERNO", "BENEFICIARIO C.I.", "PARENTESCO", "MONTO"
                    ]
                );
                foreach($data as $row) {
                    $data_header[] = [
                        $row->identity_card,
                        $row->first_name,
                        $row->second_name,
                        $row->last_name,
                        $row->mothers_last_name,
                        $row->code,
                        $row->name,
                        $row->first_name_beneficiary,
                        $row->second_name_beneficiary,
                        $row->last_name_beneficiary,
                        $row->mothers_last_name_beneficiary,
                        $row->identity_card_beneficiary,
                        $row->kinship,
                        $row->paid_amount
                    ];
                }
                break;
        }

        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "reporte_pagos_derechohabientes_" . $date;
        $type = $request->type;
        $extension = $type_file ?? '.xls';
        return Excel::download($export, $file_name . $extension);
    }
    

    public function download_qualification_report(request $request)
    {
        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $query = "SELECT
                    CONCAT(ase2.first_name,
                        CASE WHEN TRIM(ase2.second_name) <> '' THEN CONCAT(' ', ase2.second_name) ELSE '' END,
                        ' ',
                        ase2.last_name,
                        ' ',
                        ase2.second_last_name) as full_name, asq.description as question, asa2.description as answer, TO_CHAR(ase.created_at, 'YYYY-MM-DD HH24:MI:SS') as formatted_created_at
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
            ->select(
                DB::raw("ROW_NUMBER() OVER (ORDER BY e1.affiliate_id) as iterativo"),
                'e1.affiliate_id',
                'a.identity_card',
                DB::raw("CONCAT(a.first_name, ' ', COALESCE(a.second_name, ''), ' ', a.last_name, ' ', a.mothers_last_name) as affiliate_name"),
                DB::raw("(SELECT COALESCE(SUM(e2.amount), 0)
                    FROM eco_com_movements as e2
                    WHERE e2.affiliate_id = e1.affiliate_id
                    AND e2.movement_type = 'devolutions'
                    AND e2.deleted_at IS NULL) as total_devolutions"), // Añadido AND e2.deleted_at IS NULL
                DB::raw("(SELECT COALESCE(SUM(e3.amount), 0)
                    FROM eco_com_movements as e3
                    WHERE e3.affiliate_id = e1.affiliate_id
                    AND e3.movement_type = 'discount_type_economic_complement'
                    AND e3.deleted_at IS NULL) as total_discount_complement"), // Añadido AND e3.deleted_at IS NULL
                DB::raw("(SELECT COALESCE(SUM(e4.amount), 0)
                    FROM eco_com_movements as e4
                    WHERE e4.affiliate_id = e1.affiliate_id
                    AND e4.movement_type = 'eco_com_direct_payments'
                    AND e4.deleted_at IS NULL) as total_direct_payments"), // Añadido AND e4.deleted_at IS NULL
                'e1.balance'
            )
            ->join('affiliates as a', 'e1.affiliate_id', '=', 'a.id')
            ->whereNull('e1.deleted_at') // Añadido ->whereNull('e1.deleted_at')
            ->whereRaw('e1.id = (
                SELECT MAX(e2.id)
                FROM eco_com_movements as e2
                WHERE e2.affiliate_id = e1.affiliate_id
                AND e2.deleted_at IS NULL
            )')
            ->orderBy('e1.affiliate_id');
        $data = DB::table(DB::raw("({$subquery->toSql()}) as sub"))
            ->mergeBindings($subquery)
            ->orderBy('sub.iterativo')
            ->get();
        return Excel::download(new EcoComMovementsExport($data), 'eco_com_movements_report.xls');
    }
}
