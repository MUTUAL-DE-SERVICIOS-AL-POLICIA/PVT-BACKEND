<?php

namespace App\Http\Controllers;

use App\Exports\ArchivoPrimarioExport;
use App\Models\Affiliate\Affiliate;
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
        $extension = $type ?? '.xls';
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

        $list = Affiliate::leftjoin('retirement_funds', 'retirement_funds.affiliate_id', '=', 'affiliates.id')
            ->leftJoin('ret_fun_correlatives', 'ret_fun_correlatives.retirement_fund_id', '=', 'retirement_funds.id')
            ->leftJoin('contributions', 'contributions.affiliate_id', '=', 'affiliates.id')
            ->leftJoin('units', 'units.id', '=', 'contributions.unit_id')
            ->whereBetween(DB::raw('DATE(retirement_funds.created_at)'), [$start_date, $end_date])
            ->where('ret_fun_correlatives.wf_state_id', '=', 22)
            ->whereNull('ret_fun_correlatives.deleted_at')
            ->select(
                'affiliates.id as nup',
                'affiliates.identity_card as identity_card',
                'affiliates.first_name as first_name',
                'affiliates.second_name as second_name',
                'affiliates.last_name as last_name',
                'affiliates.mothers_last_name as mothers_last_name',
                'affiliates.surname_husband as surname_husband',
                'affiliates.birth_date as birth_date',
                'affiliates.date_death as date_death',
                'retirement_funds.code as code',
                'retirement_funds.reception_date as reception_date',
                'ret_fun_correlatives.code as num_cert',
                'ret_fun_correlatives.date as date',
                'affiliates.unit_police_description as unit_police_description'
            )
            ->distinct()
            ->orderBy('affiliates.id', 'asc')
            ->get();

        $data_header = array(array(
            "NRO", "NUP", "CÉDULA DE IDENTIDAD", "PRIMER NOMBRE", "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO",
            "AP. CASADA", "FECHA NACIMIENTO", "FECHA FALLECIMIENTO", "NRO TRÁMITE", "FECHA RECEPCIÓN",
            "NRO CERTIFICACIÓN", "FECHA CERTIFICACIÓN", "UNIDAD INICIO DE FUNCIONES"
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
                $row->birth_date,
                $row->date_death,
                $row->code,
                $row->reception_date,
                $row->num_cert,
                $row->date,
                $row->unit_police_description
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
}
