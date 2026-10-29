<?php

namespace App\Http\Controllers;

use App\Exports\AffiliatesSimilarExport;
use App\Exports\AffiliatesSpousesExport;
use App\Exports\ArchivoPrimarioExport;
use App\Exports\EcoComMovementsExport;
use App\Exports\ProceduresFrcamExport;
use App\Models\Affiliate\Affiliate;
use App\Models\Contribution\ContributionType;
use App\Models\RetirementFund\RetirementFund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Exports\QualificationReportExport;
use App\Exports\ReportPaymentsBeneficiariesFrcamExport;

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
                'pension_entities.name as pension_entity_name',
                'degrees.code as degree_code',
                'hierarchies.code as hierarchy_code', 
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
            ->leftJoin('pension_entities', 'pension_entities.id', '=', 'affiliates.pension_entity_id')
            ->leftJoin('degrees', 'degrees.id', '=', 'affiliates.degree_id')
            ->leftJoin('hierarchies', 'hierarchies.id', '=', 'degrees.hierarchy_id') 
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
///
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
    public function report_payments_beneficiaries2(Request $request)
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
                    //->where('ret_fun_correlatives.wf_state_id', '=', 26)
                    ->where('retirement_funds.ret_fun_state_id', '!=', 3)
                    ->whereNull('ret_fun_correlatives.deleted_at')
                   //->whereIn('discount_type_retirement_fund.discount_type_id', [1, 2, 3])
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

    public function report_affiliates_similar(Request $request)
    {        
        $sql = "
            SELECT 
                a1.id AS nup1,
                a1.identity_card AS ci1,
                a1.last_name AS last_name1,
                a1.mothers_last_name AS mothers_last_name1,
                a1.surname_husband AS surname_husband1,
                a1.first_name AS first_name1,
                a1.second_name AS second_name1,
                EXTRACT(MONTH FROM c1.month_year) AS month1,
                EXTRACT(YEAR FROM c1.month_year) AS year1,
                u1.code AS unit_code1,
                h1.code AS hierarchy_code1,
                d1.code AS degree_code1,
                c1.base_wage as base_wage1,
                c1.seniority_bonus as seniority_bonus1, 
                c1.study_bonus as study_bonus1,
                c1.position_bonus as position_bonus1, 
                c1.border_bonus as border_bonus1,
                c1.east_bonus as east_bonus1,
                c1.gain as gain1, 
                c1.total as total1,
                c1.contributionable_type as contributionable_type1,
                a2.id AS nup2,
                a2.identity_card AS ci2,
                a2.last_name AS last_name2,
                a2.mothers_last_name AS mothers_last_name2,
                a2.surname_husband AS surname_husband2,
                a2.first_name AS first_name2,
                a2.second_name AS second_name2,
                EXTRACT(MONTH FROM c2.month_year) AS month2,
                EXTRACT(YEAR FROM c2.month_year) AS year2,
                u2.code AS unit_code2,
                h2.code AS hierarchy_code2,
                d2.code AS degree_code2,
                c2.base_wage as base_wage2,
                c2.seniority_bonus as seniority_bonus2, 
                c2.study_bonus as study_bonus2,
                c2.position_bonus as position_bonus2, 
                c2.border_bonus as border_bonus2,
                c2.east_bonus as east_bonus2,
                c2.gain as gain2, 
                c2.total as total2,
                c2.contributionable_type as contributionable_type2
            FROM affiliates a1
            JOIN contributions c1 ON c1.affiliate_id = a1.id
            LEFT JOIN units u1 ON u1.id = a1.unit_id
            LEFT JOIN degrees d1 ON d1.id = a1.degree_id
            LEFT JOIN hierarchies h1 ON h1.id = d1.hierarchy_id
            JOIN affiliates a2 ON a1.id < a2.id
            JOIN contributions c2 ON c2.affiliate_id = a2.id
                AND c1.month_year = c2.month_year
            LEFT JOIN units u2 ON u2.id = a2.unit_id
            LEFT JOIN degrees d2 ON d2.id = a2.degree_id
            LEFT JOIN hierarchies h2 ON h2.id = d2.hierarchy_id
            WHERE 
                (
                    similarity(a1.identity_card, a2.identity_card) > 0.5
                    AND similarity(a1.first_name, a2.first_name) > 0.5
                    AND similarity(a1.last_name, a2.last_name) > 0.5
                    AND similarity(a1.mothers_last_name, a2.mothers_last_name) > 0.5
                    AND (
                        similarity(a1.second_name, a2.second_name) > 0.7
                        OR LEFT(a1.second_name, 1) = LEFT(a2.second_name, 1)
                    )
                )
            ORDER BY ci1 desc";

        $list = DB::select($sql);
        return Excel::download(new AffiliatesSimilarExport($list), 'affiliates_similar.xls');
    }
    ///////
        /**
     * @OA\Post(
     *      path="/api/report/report_procedures_frcam",
     *      tags={"REPORTES"},
     *      summary="GENERA REPORTE DE TRAMITES DE FONDO DE RETIRO, CUOTA Y AUXILIO MORTUORIO",
     *      operationId="report_procedures_frcam",
     *      description="Genera reporte de los trámites de fondo de retiro, cuota y auxilio mortuorio",
     *      @OA\RequestBody(
     *          description= "Reporte de trámites de fondo de retiro, cuota y auxilio mortuorio",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="start_date", type="date",description="Fecha inicio del reporte", example="2026-02-01"),
     *              @OA\Property(property="end_date", type="date",description="Fecha final del reporte", example="2026-02-28")
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

    public function report_procedures_frcam(Request $request)
    {        
        $date = date('Y-m-d');
        
        if ($request->start_date == NULL || $request->end_date == NULL) {
            $start_date = $date;
            $end_date = $date;
        } else {
            $start_date = Carbon::parse($request->start_date)->toDateString(); // Devuelve 'YYYY-MM-DD'
            $end_date = Carbon::parse($request->end_date)->toDateString();
        }

        $sql = "
            WITH last_adress AS (
                SELECT DISTINCT ON (ad.addressable_id)
                    ad.addressable_id AS affiliate_id,
                    c.name AS city,
                    ad2.zone,
                    ad2.street,
                    ad2.housing_unit,
                    ad2.number_address,
                    ad2.description
                FROM addressables ad
                JOIN addresses ad2 
                    ON ad.address_id = ad2.id
                LEFT JOIN cities c 
                    ON c.id = ad2.city_address_id
                WHERE ad.addressable_type ILIKE '%affiliates%'
                ORDER BY ad.addressable_id, ad2.updated_at DESC
            ),

            procedures AS (
                SELECT 
                    rf.code,
                    rf.procedure_modality_id,
                    rf.reception_date,
                    rf.city_start_id,
                    rf.affiliate_id
                FROM retirement_funds rf
                WHERE rf.code NOT ILIKE '%a%'
                AND rf.deleted_at IS NULL
                AND rf.reception_date BETWEEN :start_date AND :end_date

                UNION ALL

                SELECT 
                    qam.code,
                    qam.procedure_modality_id,
                    qam.reception_date,
                    qam.city_start_id,
                    qam.affiliate_id
                FROM quota_aid_mortuaries qam
                WHERE qam.code NOT ILIKE '%a%'
                AND qam.deleted_at IS NULL
                AND qam.reception_date BETWEEN :start_date AND :end_date
            )

            SELECT
                pro.code,
                pt.name AS pt_name,
                pm.name as pm_name,
                pm.shortened AS pm_shortened,
                pro.reception_date,
                c.name AS city_start,
                a.id AS nup,
                a.first_name,
                a.second_name,
                a.last_name,
                a.mothers_last_name,
                a.identity_card,
                a.date_entry,
                a.date_derelict,
                d.shortened AS degree_shortened,
                a.birth_date,
                a.date_death,
                a.civil_status,
                a.gender,
                la.city as city_address,
                la.zone,
                la.street,
                la.housing_unit,
                la.number_address,
                la.description
            FROM procedures pro
            JOIN affiliates a 
                ON a.id = pro.affiliate_id
            JOIN procedure_modalities pm 
                ON pm.id = pro.procedure_modality_id
            JOIN procedure_types pt 
                ON pm.procedure_type_id = pt.id
            JOIN cities c 
                ON pro.city_start_id = c.id
            JOIN degrees d 
                ON d.id = a.degree_id
            LEFT JOIN last_adress la
                ON la.affiliate_id = pro.affiliate_id
            ORDER BY nup, pm_shortened";
        
        $list = DB::select($sql, [
            'start_date' => $start_date,
            'end_date'   => $end_date
        ]);
        return Excel::download(new ProceduresFrcamExport($list), 'procedures_frcam_report.xls');
     }


    /**
     * @OA\Post(
     *     path="/api/report/report_payments_beneficiaries",
     *     tags={"REPORTES"},
     *     summary="GENERA REPORTE DE PAGOS Y DERECHOHABIENTES FRCAM",
     *     operationId="report_payments_beneficiaries",
     *     description="Genera reporte de trámites, titulares, beneficiarios y domicilios",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2026-02-01"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 example="2026-02-28"
     *             ),
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="Cuota Mortuoria"
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Success")
     * )
     */

    public function report_payments_beneficiaries(Request $request)
    {
        ini_set('max_execution_time', '300000');

        $date = date('Y-m-d');

        if ($request->start_date == null || $request->end_date == null) {
            $start_date = $date;
            $end_date = $date;
        } else {
            $start_date = Carbon::parse($request->start_date)->toDateString();
            $end_date = Carbon::parse($request->end_date)->toDateString();
        }

        $mainRows = [];
        $beneficiaryTable = null;

        /*
        * 1. CONSULTA PRINCIPAL
        */
        switch ($request->type) {

            case 'Fondo de Retiro Policial':

                $beneficiaryTable = 'ret_fun_beneficiaries';

                $list = Affiliate::leftJoin(
                        'retirement_funds',
                        'retirement_funds.affiliate_id',
                        '=',
                        'affiliates.id'
                    )
                    ->leftJoin(
                        'wf_states as ws_retirement',
                        'ws_retirement.id',
                        '=',
                        'retirement_funds.wf_state_current_id'
                    )
                    ->leftJoin(
                        'cities',
                        'cities.id',
                        '=',
                        'affiliates.city_identity_card_id'
                    )
                    ->leftJoin(
                        'ret_fun_beneficiaries',
                        'ret_fun_beneficiaries.retirement_fund_id',
                        '=',
                        'retirement_funds.id'
                    )
                    ->leftJoin(
                        'kinships',
                        'kinships.id',
                        '=',
                        'ret_fun_beneficiaries.kinship_id'
                    )
                    ->leftJoin(
                        'procedure_modalities',
                        'procedure_modalities.id',
                        '=',
                        'retirement_funds.procedure_modality_id'
                    )
                    ->leftJoin(
                        'discount_type_retirement_fund',
                        'discount_type_retirement_fund.retirement_fund_id',
                        '=',
                        'retirement_funds.id'
                    )
                    ->leftJoin(
                        'discount_types',
                        'discount_types.id',
                        '=',
                        'discount_type_retirement_fund.discount_type_id'
                    )
                    // Resolución: 26
                    ->leftJoin('ret_fun_correlatives as rfc_res', function ($join) {
                        $join->on(
                            'rfc_res.retirement_fund_id',
                            '=',
                            'retirement_funds.id'
                        )
                        ->where('rfc_res.wf_state_id', '=', 26)
                        ->whereNull('rfc_res.deleted_at');
                    })

                    // Liquidación: 86
                    ->leftJoin('ret_fun_correlatives as rfc_liq', function ($join) {
                        $join->on(
                            'rfc_liq.retirement_fund_id',
                            '=',
                            'retirement_funds.id'
                        )
                        ->where('rfc_liq.wf_state_id', '=', 86)
                        ->whereNull('rfc_liq.deleted_at');
                    })
                    ->whereBetween(
                        DB::raw('DATE(retirement_funds.created_at)'),
                        [$start_date, $end_date]
                    )
                    ->where('retirement_funds.ret_fun_state_id', '!=', 3)

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

                        // Beneficiario.
                        'ws_retirement.first_shortened as location',
                        'ret_fun_beneficiaries.id as beneficiary_id',
                        'ret_fun_beneficiaries.type as beneficiary_type',
                        'ret_fun_beneficiaries.state as beneficiary_state',
                        'ret_fun_beneficiaries.phone_number as phone_number',
                        'ret_fun_beneficiaries.cell_phone_number as cell_phone_number',
                        'ret_fun_beneficiaries.identity_card as beneficiary_identity_card',
                        'ret_fun_beneficiaries.first_name as beneficiary_first_name',
                        'ret_fun_beneficiaries.second_name as beneficiary_second_name',
                        'ret_fun_beneficiaries.last_name as beneficiary_last_name',
                        'ret_fun_beneficiaries.mothers_last_name as beneficiary_mothers_last_name',
                        'kinships.name as kinship',
                        'ret_fun_beneficiaries.amount_ret_fun as amount',

                        // Campos exclusivos de Fondo de Retiro.
                        'retirement_funds.total_ret_fun as total_ret_fun',
                        'retirement_funds.total_availability as total_availability',
                        'rfc_res.date as fecha_resolucion',
                        'rfc_liq.date as fecha_liquidacion',

                        DB::raw(
                            'MAX(CASE
                                WHEN discount_types.id = 1
                                THEN discount_type_retirement_fund.amount
                            END) AS advance_ret_fun'
                        ),
                        DB::raw(
                            'MAX(CASE
                                WHEN discount_types.id = 2
                                THEN discount_type_retirement_fund.amount
                            END) AS loan_retention'
                        ),
                        DB::raw(
                            'MAX(CASE
                                WHEN discount_types.id = 3
                                THEN discount_type_retirement_fund.amount
                            END) AS guarantor_retention'
                        )
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

                        'ws_retirement.first_shortened',
                        'ret_fun_beneficiaries.id',
                        'ret_fun_beneficiaries.type',
                        'ret_fun_beneficiaries.state',
                        'ret_fun_beneficiaries.phone_number',
                        'ret_fun_beneficiaries.cell_phone_number',
                        'ret_fun_beneficiaries.identity_card',
                        'ret_fun_beneficiaries.first_name',
                        'ret_fun_beneficiaries.second_name',
                        'ret_fun_beneficiaries.last_name',
                        'ret_fun_beneficiaries.mothers_last_name',
                        'ret_fun_beneficiaries.amount_ret_fun',
                        'kinships.name',

                        'retirement_funds.total_ret_fun',
                        'retirement_funds.total_availability',
                        'rfc_res.date',
                        'rfc_liq.date'
                    )
                    ->orderBy('affiliates.id')
                    ->get();

                foreach ($list as $row) {
                    $mainRows[] = [
                        'code' => $row->code,
                        'nup' => $row->nup,

                        'identity_card' => $row->identity_card,
                        'first_name' => $row->first_name,
                        'second_name' => $row->second_name,
                        'last_name' => $row->last_name,
                        'mothers_last_name' => $row->mothers_last_name,

                        'beneficiary_id' => $row->beneficiary_id,
                        'beneficiary_type' => $row->beneficiary_type,
                        'beneficiary_state' => $row->beneficiary_state,
                        'beneficiary_table' => $beneficiaryTable,
                        'location' => $row->location,

                        'beneficiary_identity_card' => $row->beneficiary_identity_card,
                        'beneficiary_first_name' => $row->beneficiary_first_name,
                        'beneficiary_second_name' => $row->beneficiary_second_name,
                        'beneficiary_last_name' => $row->beneficiary_last_name,
                        'beneficiary_mothers_last_name' => $row->beneficiary_mothers_last_name,
                        'kinship' => $row->kinship,
                        'amount' => $row->amount,
                        'phone_number' => $row->phone_number,
                        'cell_phone_number' => $row->cell_phone_number,

                        'total_ret_fun' => $row->total_ret_fun,
                        'total_availability' => $row->total_availability,
                        'fecha_resolucion' => $row->fecha_resolucion,
                        'fecha_liquidacion' => $row->fecha_liquidacion,
                        'advance_ret_fun' => $row->advance_ret_fun,
                        'loan_retention' => $row->loan_retention,
                        'guarantor_retention' => $row->guarantor_retention,
                    ];
                }

                break;

            case 'Cuota Mortuoria':
            case 'Auxilio Mortuorio':

                $beneficiaryTable = 'quota_aid_beneficiaries';

                $modalities = $request->type == 'Cuota Mortuoria'
                    ? [8, 9]
                    : [13, 14, 15];

                $list = Affiliate::leftJoin(
                        'quota_aid_mortuaries as qam',
                        'qam.affiliate_id',
                        '=',
                        'affiliates.id'
                    )
                    ->leftJoin(
                        'wf_states as ws_quota',
                        'ws_quota.id',
                        '=',
                        'qam.wf_state_current_id'
                    )
                    ->leftJoin(
                        'procedure_modalities as pm',
                        'pm.id',
                        '=',
                        'qam.procedure_modality_id'
                    )
                    ->leftJoin(
                        'quota_aid_beneficiaries as qab',
                        'qab.quota_aid_mortuary_id',
                        '=',
                        'qam.id'
                    )
                    ->leftJoin(
                        'kinships as k',
                        'k.id',
                        '=',
                        'qab.kinship_id'
                    )
                    // Resolución: 40
                    ->leftJoin('quota_aid_correlatives as qac_res', function ($join) {
                        $join->on(
                            'qac_res.quota_aid_mortuary_id',
                            '=',
                            'qam.id'
                        )
                        ->where('qac_res.wf_state_id', '=', 40)
                        ->whereNull('qac_res.deleted_at');
                    })

                    // Liquidación: 61
                    ->leftJoin('quota_aid_correlatives as qac_liq', function ($join) {
                        $join->on(
                            'qac_liq.quota_aid_mortuary_id',
                            '=',
                            'qam.id'
                        )
                        ->where('qac_liq.wf_state_id', '=', 61)
                        ->whereNull('qac_liq.deleted_at');
                    })
                    ->whereBetween(
                        DB::raw('DATE(qam.created_at)'),
                        [$start_date, $end_date]
                    )
                    ->whereNull('qam.deleted_at')
                    ->whereIn('qam.procedure_modality_id', $modalities)
                    ->where('qam.code', 'not ilike', '%A')
                    ->select(
                        'affiliates.id as nup',
                        'affiliates.identity_card',
                        'affiliates.first_name',
                        'affiliates.second_name',
                        'affiliates.last_name',
                        'affiliates.mothers_last_name',
                        'qam.code',
                        'qac_res.date as fecha_resolucion',
                        'qac_liq.date as fecha_liquidacion',

                        // Ubicación del trámite.
                        'ws_quota.first_shortened as location',

                        // Beneficiario.
                        'qab.id as beneficiary_id',
                        'qab.type as beneficiary_type',
                        'qab.state as beneficiary_state',
                        'qab.phone_number as phone_number',
                        'qab.cell_phone_number as cell_phone_number',
                        'qab.first_name as beneficiary_first_name',
                        'qab.second_name as beneficiary_second_name',
                        'qab.last_name as beneficiary_last_name',
                        'qab.mothers_last_name as beneficiary_mothers_last_name',
                        'qab.identity_card as beneficiary_identity_card',
                        'k.name as kinship',
                        'qab.paid_amount as amount'
                    )
                    ->groupBy(
                        'affiliates.id',
                        'affiliates.identity_card',
                        'affiliates.first_name',
                        'affiliates.second_name',
                        'affiliates.last_name',
                        'affiliates.mothers_last_name',
                        'qam.code',
                        'ws_quota.first_shortened',

                        'qab.id',
                        'qab.type',
                        'qab.state',
                        'qab.phone_number',
                        'qab.cell_phone_number',
                        'qab.first_name',
                        'qab.second_name',
                        'qab.last_name',
                        'qab.mothers_last_name',
                        'qab.identity_card',
                        'k.name',
                        'qab.paid_amount',
                        
                        'qac_res.date',
                        'qac_liq.date'
                    )
                    ->orderBy('affiliates.identity_card')
                    ->get();

                foreach ($list as $row) {
                    $mainRows[] = [
                        'code' => $row->code,
                        'nup' => $row->nup,

                        'identity_card' => $row->identity_card,
                        'first_name' => $row->first_name,
                        'second_name' => $row->second_name,
                        'last_name' => $row->last_name,
                        'mothers_last_name' => $row->mothers_last_name,

                        'beneficiary_id' => $row->beneficiary_id,
                        'beneficiary_type' => $row->beneficiary_type,
                        'beneficiary_state' => $row->beneficiary_state,
                        'beneficiary_table' => $beneficiaryTable,
                        'location' => $row->location,

                        'beneficiary_identity_card' => $row->beneficiary_identity_card,
                        'beneficiary_first_name' => $row->beneficiary_first_name,
                        'beneficiary_second_name' => $row->beneficiary_second_name,
                        'beneficiary_last_name' => $row->beneficiary_last_name,
                        'beneficiary_mothers_last_name' => $row->beneficiary_mothers_last_name,
                        'kinship' => $row->kinship,
                        'amount' => $row->amount,
                        'phone_number' => $row->phone_number,
                        'cell_phone_number' => $row->cell_phone_number,

                        // Fechas de resolución y liquidación para Cuota/Auxilio.
                        'fecha_resolucion' => $row->fecha_resolucion,
                        'fecha_liquidacion' => $row->fecha_liquidacion,
                    ];
                }

                break;

            default:
                abort(422, 'Tipo de reporte no válido.');
        }

        /*
        * 2. CONSULTA COMPLEMENTARIA DEL TRÁMITE
        */
        $procedureMap = [];

        if (!empty($mainRows)) {
            $pairs = [];
            $bindings = [];
            $seen = [];

            foreach ($mainRows as $row) {
                if ($row['nup'] === null || $row['code'] === null) {
                    continue;
                }

                $key = $row['nup'] . '|' . $row['code'];

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $pairs[] = '(pro.affiliate_id = ? AND pro.code = ?)';
                    $bindings[] = $row['nup'];
                    $bindings[] = $row['code'];
                }
            }

            if (!empty($pairs)) {
                $wherePairs = implode(' OR ', $pairs);

                $sql = "
                    WITH procedures AS (
                        SELECT
                            rf.code,
                            rf.procedure_modality_id,
                            rf.reception_date,
                            rf.city_start_id,
                            rf.affiliate_id
                        FROM retirement_funds rf
                        WHERE rf.deleted_at IS NULL

                        UNION ALL

                        SELECT
                            qam.code,
                            qam.procedure_modality_id,
                            qam.reception_date,
                            qam.city_start_id,
                            qam.affiliate_id
                        FROM quota_aid_mortuaries qam
                        WHERE qam.deleted_at IS NULL
                    )
                    SELECT
                        pro.code,
                        pro.affiliate_id AS nup,
                        pt.name AS type_name,
                        pm.name AS modality_name,
                        pm.shortened AS modality_shortened,
                        pro.reception_date,
                        c.name AS city_start,
                        a.date_entry,
                        a.date_derelict,
                        d.shortened AS degree_shortened,
                        a.birth_date,
                        a.date_death,
                        a.civil_status,
                        a.gender
                    FROM procedures pro
                    JOIN affiliates a
                        ON a.id = pro.affiliate_id
                    JOIN procedure_modalities pm
                        ON pm.id = pro.procedure_modality_id
                    JOIN procedure_types pt
                        ON pt.id = pm.procedure_type_id
                    LEFT JOIN cities c
                        ON c.id = pro.city_start_id
                    LEFT JOIN degrees d
                        ON d.id = a.degree_id
                    WHERE ($wherePairs)
                ";

                $procedures = DB::select($sql, $bindings);

                foreach ($procedures as $procedure) {
                    $key = $procedure->nup . '|' . $procedure->code;
                    $procedureMap[$key] = $procedure;
                }
            }
        }

        /*
        * 3. DIRECCIONES DE BENEFICIARIOS TYPE = 'S'
        */
        $addressMap = [];

        $retirementBeneficiaryIds = [];
        $quotaBeneficiaryIds = [];

        foreach ($mainRows as $row) {
            if (
                $row['beneficiary_id'] === null ||
                $row['beneficiary_type'] !== 'S'
            ) {
                continue;
            }

            if ($row['beneficiary_table'] === 'ret_fun_beneficiaries') {
                $retirementBeneficiaryIds[] = $row['beneficiary_id'];
            } elseif ($row['beneficiary_table'] === 'quota_aid_beneficiaries') {
                $quotaBeneficiaryIds[] = $row['beneficiary_id'];
            }
        }

        $retirementBeneficiaryIds = array_values(
            array_unique($retirementBeneficiaryIds)
        );

        $quotaBeneficiaryIds = array_values(
            array_unique($quotaBeneficiaryIds)
        );

        // Dirección de beneficiarios de Fondo de Retiro.
        if (!empty($retirementBeneficiaryIds)) {
            $addresses = DB::table('ret_fun_beneficiaries as b')
                ->join('addressables as ab', function ($join) {
                    $join->on('ab.addressable_id', '=', 'b.id')
                        ->where(
                            'ab.addressable_type',
                            '=',
                            'ret_fun_beneficiaries'
                        );
                })
                ->join('addresses as ad', 'ad.id', '=', 'ab.address_id')
                ->leftJoin('cities as c', 'c.id', '=', 'ad.city_address_id')
                ->where('b.type', '=', 'S')
                ->whereIn('b.id', $retirementBeneficiaryIds)
                ->select(
                    'b.id as beneficiary_id',
                    'c.name as city_address',
                    'ad.zone',
                    'ad.street',
                    'ad.number_address'
                )
                ->get();

            foreach ($addresses as $address) {
                $parts = [
                    $address->zone,
                    $address->street,
                    $address->number_address,
                ];

                $parts = array_filter($parts, function ($value) {
                    return $value !== null && trim((string) $value) !== '';
                });

                $addressMap[
                    'ret_fun_beneficiaries|' . $address->beneficiary_id
                ] = [
                    'city_address' => $address->city_address,
                    'address' => implode(' ', $parts),
                ];
            }
        }

        // Dirección de beneficiarios de Cuota/Auxilio Mortuorio.
        if (!empty($quotaBeneficiaryIds)) {
            $addresses = DB::table('quota_aid_beneficiaries as b')
                ->join('addressables as ab', function ($join) {
                    $join->on('ab.addressable_id', '=', 'b.id')
                        ->where(
                            'ab.addressable_type',
                            '=',
                            'quota_aid_beneficiaries'
                        );
                })
                ->join('addresses as ad', 'ad.id', '=', 'ab.address_id')
                ->leftJoin('cities as c', 'c.id', '=', 'ad.city_address_id')
                ->where('b.type', '=', 'S')
                ->whereIn('b.id', $quotaBeneficiaryIds)
                ->select(
                    'b.id as beneficiary_id',
                    'c.name as city_address',
                    'ad.zone',
                    'ad.street',
                    'ad.number_address'
                )
                ->get();

            foreach ($addresses as $address) {
                $parts = [
                    $address->zone,
                    $address->street,
                    $address->number_address,
                ];

                $parts = array_filter($parts, function ($value) {
                    return $value !== null && trim((string) $value) !== '';
                });

                $addressMap[
                    'quota_aid_beneficiaries|' . $address->beneficiary_id
                ] = [
                    'city_address' => $address->city_address,
                    'address' => implode(' ', $parts),
                ];
            }
        }

        /*
        * 4. CONSTRUCCIÓN DE FILAS
        */
        $data = [];
        $counter = 1;

        $isRetirementFund =
            $request->type === 'Fondo de Retiro Policial';

        foreach ($mainRows as $row) {
            $procedureKey = $row['nup'] . '|' . $row['code'];

            $extra = isset($procedureMap[$procedureKey])
                ? $procedureMap[$procedureKey]
                : null;

            $addressKey = $row['beneficiary_table'] . '|'
                . $row['beneficiary_id'];

            $address = null;

            if (
                $row['beneficiary_type'] === 'S' &&
                isset($addressMap[$addressKey])
            ) {
                $address = $addressMap[$addressKey];
            }

            // Primeras 20 columnas comunes.
            $result = [
                $counter++,
                $row['code'],
                $extra ? $extra->type_name : null,
                $extra ? $extra->modality_name : null,
                $extra ? $extra->modality_shortened : null,
                $extra ? $extra->reception_date : null,
                $extra ? $extra->city_start : null,
                $row['nup'],
                $row['identity_card'],
                $row['first_name'],
                $row['second_name'],
                $row['last_name'],
                $row['mothers_last_name'],
                $extra ? $extra->date_entry : null,
                $extra ? $extra->date_derelict : null,
                $extra ? $extra->degree_shortened : null,
                $extra ? $extra->birth_date : null,
                $extra ? $extra->date_death : null,
                $extra ? $extra->civil_status : null,
                $extra ? $extra->gender : null,
            ];

            if ($isRetirementFund) {
                // Siete columnas exclusivas de Fondo de Retiro.
                $result = array_merge($result, [
                    $row['total_ret_fun'],
                    $row['total_availability'],
                    $row['fecha_resolucion'],
                    $row['fecha_liquidacion'],
                    $row['advance_ret_fun'],
                    $row['loan_retention'],
                    $row['guarantor_retention'],
                ]);
            } else {
                // Resolución para Cuota Mortuoria y Auxilio Mortuorio.
                $result = array_merge($result, [
                    $row['fecha_resolucion'],
                    $row['fecha_liquidacion'],
                ]);
            }

            $documents = filter_var(
                $row['beneficiary_state'],
                FILTER_VALIDATE_BOOLEAN
            )
                ? 'Documentos completos'
                : 'Documentos incompletos';

            $result = array_merge($result, [
                $row['beneficiary_identity_card'],
                $row['beneficiary_first_name'],
                $row['beneficiary_second_name'],
                $row['beneficiary_last_name'],
                $row['beneficiary_mothers_last_name'],
                $row['kinship'],
                $row['amount'],
                $address ? $address['city_address'] : null,
                $address ? $address['address'] : null,
                $row['phone_number'],
                $row['cell_phone_number'],
                $row['location'],
                $documents,
            ]);

            $data[] = $result;
        }

        $export = new ReportPaymentsBeneficiariesFrcamExport(
            $data,
            $request->type
        );

        $file_name = 'reporte_pagos_derechohabientes_' . $date;

        return Excel::download($export, $file_name . '.xls');
    }
}
