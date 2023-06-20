<?php

namespace App\Http\Controllers\Contribution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Auth;
use App\Http\Controllers\Contribution\ImportPayrollSenasirController;
use App\Models\Contribution\PayrollCommand;
use App\Models\Contribution\Contribution;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArchivoPrimarioExport;

class ImportContributionCommandController extends Controller
{
    
     //
      /**
     * @OA\Post(
     *      path="/api/contribution/list_months_import_contribution_command",
     *      tags={"IMPORTACION-APORTES-COMANDO"},
     *      summary="LISTA LOS MESES QUE SE REALIZARON IMPORTACIONES A LA TABLA CONTRIBUTIONS DE COMANDO EN BASE A UN AÑO DADO EJ:2022",
     *      operationId="list_command_months",
     *      description="Lista los meses importados en la tabla contributions enviando como parámetro un año en específico",
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

    public function list_months_import_contribution_command(Request $request)
    {
        $request->validate([
            'period_year' => 'required|date_format:"Y"',
            'with_data_count'=>'boolean'
        ]);
         $with_data_count = !isset($request->with_data_count) || is_null($request->with_data_count)? true:$request->with_data_count;
         $period_year = $request->get('period_year');
         $contributionable_type = 'payroll_commands';

         $query = "SELECT distinct month_year, to_char( month_year, 'TMMonth') as period_month_name, extract(year from month_year) as period_year,extract(month from month_year) as period_month  from contributions where deleted_at is null and (extract(year from month_year::timestamp)) = $period_year and contributionable_type = 'payroll_commands' group by month_year;";
         $query = DB::select($query);

         $query_months = "select id as period_month ,name as period_month_name from months order by id asc";
         $query_months = DB::select($query_months);

         foreach ($query_months as $month) {
            $month->state_importation = false;
            foreach ($query as $month_contribution) {
                if($month->period_month == $month_contribution->period_month){
                    $month->state_importation = true;
                    break;
                }
            }
            $month->state_validated_payroll = PayrollCommand::data_period($month->period_month,$period_year)['exist_data'];
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

    public function data_count($month,$year,$date_payroll_format){
        $data_count['num_total_data_copy'] = 0;
        $data_count['num_data_validated'] = 0;
        $data_count['num_data_regular'] = 0;
        $data_count['num_data_new'] = 0;
        $data_count['num_total_data_contributions'] = 0;
        $data_count['sum_amount_total_contributions'] = 0;

        //---TOTAL DE DATOS DEL ARCHIVO
        $query_total_data = "SELECT count(id) FROM payroll_copy_commands where mes = $month::INTEGER and a_o = $year::INTEGER;";
        $query_total_data = DB::connection('db_aux')->select($query_total_data);
        $data_count['num_total_data_copy'] = $query_total_data[0]->count;
        // TOTAL VALIDADOS
        $data_count['num_data_validated'] =PayrollCommand::data_count($month,$year)['validated'];
        //CANTIDAD DE AFILIADOS REGULARES
        $data_count['num_data_regular'] = PayrollCommand::data_count($month,$year)['regular'];
        //CANTIDAD DE AFILIADOS NUEVOS
        $data_count['num_data_new'] =PayrollCommand::data_count($month,$year)['new'];
        //---TOTAL DE REGISTROS CONTRIBUTION PASSIVES
        $data_count['num_total_data_contributions'] = Contribution::data_period_command($date_payroll_format)['count_data'];
        //---suma monto total contribucion
        $data_count['sum_amount_total_contributions'] = floatval(Contribution::sum_total_command($date_payroll_format));

        return  $data_count;
    }
    public function data_count_contribution($month,$year,$date_payroll_format){
        $data_count['num_data_validated'] = 0;
        $data_count['num_total_data_contributions'] = 0;
        $data_count['sum_amount_total_contributions'] = 0;
        // TOTAL VALIDADOS
        $data_count['num_data_validated'] =PayrollCommand::data_count($month,$year)['validated'];
        $data_count['num_total_data_contributions'] = Contribution::data_period_command($date_payroll_format)['count_data'];
        //---suma monto total contribucion
        $data_count['sum_amount_total_contributions'] = floatval(Contribution::sum_total_command($date_payroll_format));

        return  $data_count;
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/import_contribution_command",
     *      tags={"IMPORTACION-APORTES-COMANDO"},
     *      summary="PASO 3 IMPORTACIÓN DE CONTRIBUCION COMANDO GENERAL",
     *      operationId="import_contribution_command",
     *      description="Importación de aportes de Comando general",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="period_contribution", type="string",description="fecha de aporte required",example= "2022-03-01")
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
    public function import_contribution_command(Request $request){
        $request->validate([
        'period_contribution' => 'required|date_format:"Y-m-d"',
        ]);
     try{
        DB::beginTransaction();
        $user_id = Auth::user()->id;
        $message ='No realizado la importación!';
        $count_created = 0;
        $successfully = false;
        $period_contribution = Carbon::parse($request->period_contribution);
        $year = (int)$period_contribution->format("Y");
        $month = (int)$period_contribution->format("m");
        $count_registered = Contribution::data_period_command($request->period_contribution)['count_data'];
        if((int)$count_registered > 0){
            $message ="Error al realizar la importación, el periodo ya fue importado.";
        }else{
            if(Contribution::exist_contribution_rate($request->period_contribution)){
                $query ="select import_period_contribution_command('$request->period_contribution',$user_id,$year,$month)";
                $query = DB::select($query);
                $count_created = Contribution::data_period_command($request->period_contribution)['count_data'];
                DB::commit();
                $successfully = true;
                $message ="Realizado con éxito!";
            }else{
                $message ="No existe la taza de contribución para el periodo : ".$request->period_contribution.", el dato es requerido para continuar.";
            }
        }
        return response()->json([
            'message' => $message,
            'payload' => [
                'successfully' => $successfully,
                'num_created' => $count_created,
            ],
        ]);
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
     *      path="/api/contribution/report_import_contribution_command",
     *      tags={"IMPORTACION-APORTES-COMANDO"},
     *      summary="GENERA REPORTE DE APORTES COMANDO IMPORTADAS",
     *      operationId="report_import_contribution_command",
     *      description="Genera reporte de aportes COMANDO de la tabla contribution de acuerdo a periodo de planilla",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_contribution", type="string",description="fecha de planilla required",example= "2022-03-01")
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
    public function report_import_contribution_command(request $request) {

        $request->validate([
            'date_contribution' => 'required|date_format:"Y-m-d"',
        ]);

        DB::beginTransaction();
        $message = "No hay datos";
        ini_set('max_execution_time', '300');
        $date_contribution_format = $request->date_contribution;

        $data_cabeceras=array(array("PERIODO","TIPO","ID_AFILIADO","CÉDULA_DE_IDENTIDAD","UNIDAD","DESGLOSE","PATERNO",
        "MATERNO","AP_CASADA", "P_NOMBRE","S_NOMBRE","ESTADO_CIVIL","GRADO", "CATEGORÍA","SUELDO_BASE","BONO_ANTIGÜEDAD", "BONO_ESTUDIO",
        "BONO_A_CARGO","BONO_FRONTERA","BONO_ORIENTE","TOTAL_GANADO","LÍQUIDO_PAGABLE","COTIZABLE","FONDO_DE_RETIRO",
        "CUOTA_MORTUORIA","TOTAL_APORTE"));

        $date_contribution = Carbon::parse($request->date_contribution);
        $year = (string)$date_contribution->format("Y");
        $month = (string)$date_contribution->format("m");
        $day = (string)$date_contribution->format("d");
        $date_contribution = $year.'-'.$month.'-'.$day;     
        $data_contribution_command = "select c.month_year, c.type, a.id, a.identity_card, u.name, b.name as breakdown, a.last_name, a.mothers_last_name, 
        a.surname_husband, a.first_name, a.second_name, a.civil_status, d.name as degree, c3.name as category, c.base_wage, c.seniority_bonus,c.study_bonus, 
        c.position_bonus, c.border_bonus, c.east_bonus, c.gain, c.payable_liquid, c.quotable, c.retirement_fund, c.mortuary_quota, c.total
        from contributions c 
        inner join affiliates a 
        on c.affiliate_id = a.id
        inner join units u 
        on u.id = c.unit_id 
        inner join breakdowns b 
        on b.id = c.breakdown_id 
        inner join degrees d 
        on d.id = c.degree_id
        inner join categories c3 
        on c3.id = c.category_id 
        and c.month_year = '$date_contribution'
        and c.type = 'Planilla'";
                    $data_contribution_command = DB::select($data_contribution_command);

                            if(count($data_contribution_command)> 0){
                                $message = "Excel";
                                foreach ($data_contribution_command as $row){
                                    array_push($data_cabeceras, array($row->month_year ,$row->type ,$row->id ,$row->identity_card,
                                    $row->name, $row->breakdown, $row->last_name , $row->mothers_last_name ,$row->surname_husband, $row->first_name,
                                    $row->second_name,$row->civil_status,$row->degree,$row->category,$row->base_wage,$row->seniority_bonus,$row->study_bonus,$row->position_bonus,
                                    $row->border_bonus, $row->east_bonus,$row->gain, $row->payable_liquid,$row->quotable,
                                    $row->retirement_fund,$row->mortuary_quota,$row->total));
                                }

                                $export = new ArchivoPrimarioExport($data_cabeceras);
                                $file_name = "Aportes_Comando";
                                $extension = '.xls';
                                return Excel::download($export, $file_name."_".$month.$year."_".$extension);

                            }else{
                                return response()->json([
                                    'message' => "Error no existe archivo Comando del periodo indicado para mostrar",                                    
                                    ],
                                );
                            }         
    }
}
