<?php

namespace App\Http\Controllers\Contribution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Contribution\ImportPayrollSenasirController;
use Auth;
use App\Models\Contribution\PayrollSenasir;
use App\Models\Contribution\ContributionPassive;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArchivoPrimarioExport;


class ImportContributionSenasirController extends Controller
{
     //
      /**
     * @OA\Post(
     *      path="/api/contribution/list_months_import_contribution_senasir",
     *      tags={"IMPORTACION-APORTES-SENASIR"},
     *      summary="LISTA LOS MESES QUE SE REALIZARON IMPORTACIONES A LA TABLA CONTRIBUTION PASSIVES SENASIR EN BASE A UN AÑO DADO EJ:2021",
     *      operationId="list_senasir_months",
     *      description="Lista los meses importados en la tabla contribution_passives enviando como parametro un año en especifico",
     *     @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *             @OA\Property(property="period_year", type="integer",description="Año de contribución a listar",example= "2022"),
     *             @OA\Property(property="with_data_count", type="boolean",description="valor para pedir envio de conteo de datos",example= false)
     *            )
     *
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
    public function list_months_import_contribution_senasir(Request $request)
     {
        $request->validate([
            'period_year' => 'required|date_format:"Y"',
            'with_data_count'=>'boolean'
        ]);
        $with_data_count = !isset($request->with_data_count) || is_null($request->with_data_count)? true:$request->with_data_count;
         $period_year = $request->get('period_year');
         $contributionable_type = 'payroll_senasirs';

         $query = "SELECT  distinct month_year, to_char( month_year, 'TMMonth') as period_month_name, extract(year from month_year) as period_year,extract(month from month_year) as period_month from contribution_passives where deleted_at  is null and  (extract(year from month_year::timestamp)) = $period_year and contributionable_type = 'payroll_senasirs' group by month_year;";
         $query = DB::select($query);

         $query_months = "select id as period_month ,name  as period_month_name from months order by id asc";
         $query_months = DB::select($query_months);

         foreach ($query_months as $month) {
            $month->state_importation = false;
            foreach ($query as $month_contribution) {
                if($month->period_month == $month_contribution->period_month){
                    $month->state_importation = true;
                    break;
                }
            }
            $month->state_validated_payroll = PayrollSenasir::data_period($month->period_month,$period_year)['exist_data'];
            $date_payroll_format = Carbon::parse($period_year.'-'.$month->period_month.'-'.'01')->toDateString();
            if($with_data_count)
            $month->data_count = $this->data_count_contribution($month->period_month,$period_year,$date_payroll_format);
         }

         return response()->json([
            'message' => "Exito",
            'payload' => [
                'list_months' =>  $query_months,
                'count_months' =>  count($query)
            ],
        ]);
     }

    public function data_count_contribution($month,$year,$date_payroll_format){
        $data_count['num_data_validated'] = 0;
        $data_count['num_total_data_contribution_passives'] = 0;
        $data_count['sum_amount_total_contribution_passives'] = 0;
        //---NUMERO DE DATOS VALIDADOS
        $data_count['num_data_validated'] = PayrollSenasir::data_period($month,$year)['count_data'];
        //---TOTAL DE REGISTROS CONTRIBUTION PASSIVES
        $data_count['num_total_data_contribution_passives'] = ContributionPassive::data_period_senasir($date_payroll_format)['count_data'];
        //---suma monto total contribucion
        $data_count['sum_amount_total_contribution_passives'] = floatval(ContributionPassive::sum_total_senasir($date_payroll_format));

        return  $data_count;
    }
    /**
     * @OA\Post(
     *      path="/api/contribution/import_create_or_update_contribution_period_senasir",
     *      tags={"IMPORTACION-APORTES-SENASIR"},
     *      summary="PASO 3 IMPORTACIÓN REGISTRO O ACTUALIZACIÓN DE DATOS DE CONTRIBUCION SENASIR",
     *      operationId="import_create_or_update_contribution_period_senasir",
     *      description="Creacion o actualizacion de contribution_passives",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="period_contribution", type="string",description="fecha de planilla required",example= "2021-10-01")
     *            )
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
    public function import_create_or_update_contribution_period_senasir(Request $request){
        $request->validate([
        'period_contribution' => 'required|date_format:"Y-m-d"',
        ]);
     try{
            DB::beginTransaction();
        $user_id = Auth::user()->id;
        $successfully = false;
        $period_contribution = Carbon::parse($request->period_contribution);
        $year = (int)$period_contribution->format("Y");
        $month = (int)$period_contribution->format("m");
        $count_registered = ContributionPassive::data_period_senasir($request->period_contribution)['count_data'];
        if((int)$count_registered > 0){
            return response()->json([
                'message' => "Error al realizar la importación, el periodo ya fue importado.",
                'payload' => [
                    'successfully' => $successfully
                ],
            ]);
        }else{
            $query ="select import_period_contribution_senasir('$request->period_contribution',$user_id,$year,$month)";
            $query = DB::select($query);
            $count_created = ContributionPassive::data_period_senasir($request->period_contribution)['count_data'];
            DB::commit();
            $successfully = true;
            return response()->json([
                'message' => "Realizado con éxito!",
                'payload' => [
                    'successfully' => $successfully,
                    'num_created' => $count_created,
                ],
            ]);
        }
     }catch(Exception $e){
        DB::rollBack();
        return response()->json([
            'message' => 'Error al realizar la importación',
            'payload' => [
                'successfully' => false,
                'error' => $e->getMessage(),
            ],
        ]);
     }
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/report_import_contribution_senasir",
     *      tags={"IMPORTACION-APORTES-SENASIR"},
     *      summary="GENERA REPORTE DE APORTES SENASIR IMPORTADAS",
     *      operationId="report_import_contribution_senasir",
     *      description="Genera reporte de aportes SENASIR de la tabla contribution_passives de acuerdo a periodo",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_contribution", type="string",description="fecha de planilla required",example= "2021-10-01")
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
    public function report_import_contribution_senasir(request $request) {

        $request->validate([
            'date_contribution' => 'required|date_format:"Y-m-d"',
        ]);

        DB::beginTransaction();
        $message = "No hay datos";
        $date_contribution_format = $request->date_contribution;

        $data_cabeceras=array(array("ID_AFILIADO","FECHA","CARNET","MATRÍCULA","PRIMER NOMBRE","SEGUNDO NOMBRE","APELLIDO PATERNO","APELLIDO MATERNO", 
        "APELLIDO CASADA","COTIZABLE","RENTA","RENTA DIGNIDAD","APORTE","CLASE DE RENTA"));

        $date_contribution = Carbon::parse($request->date_contribution);
        $year = (string)$date_contribution->format("Y");
        $month = (string)$date_contribution->format("m");
        $day = (string)$date_contribution->format("d");
        $date_contribution = $year.'-'.$month.'-'.$day;     
        $data_contribution_senasir = "select  * from  contribution_passives cp
        inner join affiliates a 
        on cp.affiliate_id = a.id 
        and cp.month_year = '$date_contribution'
        and cp.contributionable_type = 'payroll_senasirs'";
                    $data_contribution_senasir = DB::select($data_contribution_senasir);

                            if(count($data_contribution_senasir)> 0){
                                $message = "Excel";
                                foreach ($data_contribution_senasir as $row){
                                    array_push($data_cabeceras, array($row->affiliate_id ,$row->month_year ,$row->identity_card ,$row->registration, $row->first_name,
                                    $row->second_name, $row->last_name, $row->mothers_last_name, $row->surname_husband , $row->quotable, $row->rent_pension, $row->dignity_rent, $row->total,
                                    $row->affiliate_rent_class));
                                }

                                $export = new ArchivoPrimarioExport($data_cabeceras);
                                $file_name = "Aportes_Senasir";
                                $extension = '.xls';
                                return Excel::download($export, $file_name.$month.$year.$extension);

                            }else{
                                return response()->json([
                                    'message' => "Error no existe archivo Senasir del periodo indicado para mostrar",                                    
                                    ],
                                );
                            }         
    }
}
