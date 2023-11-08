<?php

namespace App\Http\Controllers;

use App\Exports\ArchivoPrimarioExport;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\Spouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/report/report_affiliates_spouses",
     *      tags={"REPORTES"},
     *      summary="GENERA REPORTE DE AFILIADOS - CÓNYUGES",
     *      operationId="report_affiliates_spouses",
     *      description="Genera reporte de los afiliados y sus cónyuges",
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
    public function report_affiliates_spouses()
    {
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
                'spouses.birth_date as spouse_birth_date',
                'affiliates.registration as registration'
            )
            ->orderBy('affiliates.id', 'asc')->get();

        $data_header = array(array(
            "NRO", "NUP", "CI TITULAR", "PRIMER NOMBRE", "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO",
            "AP. CASADA", "FECHA DE INGRESO", "FECHA DE NACIMIENTO", "CI VIUDA(O)", "PRIMER NOMBRE",
            "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO", "AP. CASADA", "FECHA DE NACIMIENTO", "MATRÍCULA"
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
                $row->spouse_birth_date,
                $row->registration
            ));
            $i++;
        }
        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "reporte_afiliados_conyuges";
        $extension = '.xlsx';
        return Excel::download($export, $file_name . $extension);
    }

    /**
     * @OA\Get(
     *      path="/api/report/report_retirement_funds",
     *      tags={"REPORTES"},
     *      summary="GENERA REPORTE DE FONDO DE RETIRO",
     *      operationId="report_retirement_funds",
     *      description="Genera reporte de los trámites de fondo de retiro",
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
    public function report_retirement_funds()
    {
        $list = Affiliate::leftjoin('retirement_funds', 'retirement_funds.affiliate_id', '=', 'affiliates.id')
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
                'affiliates.unit_police_description as unit_police_description'
            )
            ->orderBy('affiliates.id', 'asc')->get();

        $data_header = array(array(
            "NRO", "NUP", "CÉDULA DE IDENTIDAD", "PRIMER NOMBRE", "SEGUNDO NOMBRE", "AP. PATERNO", "AP. MATERNO",
            "AP. CASADA", "FECHA DE NACIMIENTO", "FECHA DE FALLECIMIENTO", "NRO DE TRÁMITE", "FECHA DE RECEPCIÓN",
            "UNIDAD"
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
                $row->unit_police_description
            ));
            $i++;
        }
        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "reporte_fondo_de_retiro";
        $extension = '.xlsx';
        return Excel::download($export, $file_name . $extension);
    }

    /**
     * @OA\Get(
     *      path="/api/report/report_payments_beneficiaries",
     *      tags={"REPORTES"},
     *      summary="GENERA REPORTE DE PAGOS Y DERECHOHABIENTES",
     *      operationId="report_payments_beneficiaries",
     *      description="Genera reporte de los pagos y derechohabientes de los beneficios",
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
    public function report_payments_beneficiaries()
    {
        $list = Affiliate::leftjoin('retirement_funds', 'retirement_funds.affiliate_id', '=', 'affiliates.id')
            ->leftjoin('cities', 'cities.id', '=', 'affiliates.city_identity_card_id')
            ->leftjoin('ret_fun_beneficiaries', 'ret_fun_beneficiaries.retirement_fund_id', '=', 'retirement_funds.id')
            ->leftjoin('kinships', 'kinships.id', '=', 'ret_fun_beneficiaries.kinship_id')
            ->leftjoin('procedure_modalities', 'procedure_modalities.id', '=', 'retirement_funds.procedure_modality_id')
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
                DB::raw('concat_full_name(affiliates.first_name, affiliates.second_name, affiliates.last_name, affiliates.mothers_last_name, affiliates.surname_husband) as beneficiary_full_name'),
                'ret_fun_beneficiaries.first_name as beneficiary_first_name',
                'kinships.name as kinship',
                'ret_fun_beneficiaries.amount_ret_fun as amount_ret_fun'
            )
            ->orderBy('affiliates.id', 'asc')
            ->get();

        $dataHeader = array(
            [
                "NRO", "NUP", "NRO TRÁMITE", "MODALIDAD", "PRIMER NOMBRE", "SEGUNDO NOMBRE", "AP. PATERNO",
                "AP. MATERNO", "AP. CASADA", "CÉDULA DE IDENTIDAD", "EXPEDICIÓN", "TOTAL FONDO DE RETIRO",
                "TOTAL DISPONIBILIDAD", "BENEFICIARIO", "PARENTESCO", "MONTO PARA BENEFICIARIOS"
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
                $row->beneficiary_full_name,
                $row->kinship,
                $row->amount_ret_fun
            ];
            $i++;
        }
        $export = new ArchivoPrimarioExport($dataHeader);
        $fileName = "reporte_pagos_derechohabientes";
        $extension = '.xlsx';
        return Excel::download($export, $fileName . $extension);
    }
}
