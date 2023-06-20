<?php

namespace App\Http\Controllers\Contribution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArchivoPrimarioExport;
use App\Helpers\Util;
use App\Models\Contribution\PayrollSenasir;

class ImportPayrollSenasirController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/contribution/upload_copy_payroll_senasir",
     *      tags={"IMPORTACION-PLANILLA-SENASIR"},
     *      summary="PASO 1 COPIADO DE DATOS PLANILLA SENASIR",
     *      operationId="upload_copy_payroll_senasir",
     *      description="Copiado de datos del archivo de planillas senasir a la tabla contribution_passives_copy_payroll_senasir",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *            @OA\Property(property="file", type="file", description="file required", example="file"),
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "2021-10-01")
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
    //copiado de datos a la tabla

     public function upload_copy_payroll_senasir(request $request)
     {
         $request->validate([
             'file' => 'required',
             'date_payroll' => 'required|date_format:"Y-m-d"',
         ]);

         $extencion = strtolower($request->file->getClientOriginalExtension());
         $file_name_entry = $request->file->getClientOriginalName();
        DB::beginTransaction();
         try{
             $username = env('FTP_USERNAME');
             $password = env('FTP_PASSWORD');
             if($extencion == "csv"){
                 $date_payroll = Carbon::parse($request->date_payroll);
                 $year = $date_payroll->format("Y");
                 $month = $date_payroll->format("m");
                 $date_payroll_format = $request->date_payroll;

                 $existing_period = "select  count(*) from  payroll_copy_senasirs  where mes ='$month' and a_o='$year'";
                 $existing_period = DB::connection('db_aux')->select($existing_period)[0]->count;
                    $this->delete_payroll_copy_senasirs($month,$year);
                     $file_name = "senasir-".$month."-".$year.'.'.$extencion;
                     if($file_name_entry == $file_name){
                         $base_path = 'planillas/planilla_senasir';
                         $file_path = Storage::disk('ftp')->putFileAs($base_path,$request->file,$file_name);
                         $base_path ='ftp://'.env('FTP_HOST').env('FTP_ROOT').$file_path;

                         $copy = "copy payroll_copy_senasirs(a_o, mes, id_person_titular, matricula_titular, mat_dh,departamento,regional,renta,tipo_renta,
                                  carnet,num_com, paterno, materno, p_nombre,s_nombre, ap_casada, fecha_nacimiento, clase_renta,total_ganado, total_descuentos,
                                  liquido_pagable, rentegro_r_basica, renta_dignidad, reintegro_renta_dignidad, reintegro_aguinaldo,reintegro_importe_adicional,
                                  reintegro_inc_gestion, descuento_aporte_muserpol, descuento_covipol, descuento_prestamo_musepol,carnet_tit,
                                  num_com_tit, pat_titular, mat_titular, p_nom_titular, s_nombre_titular, ap_casada_titular, fecha_nac_titular,
                                  clase_renta_tit, fec_fail_tit) FROM PROGRAM 'wget -q -O - $@  --user=$username --password=$password $base_path' WITH DELIMITER ':' CSV header;";
                         $copy = DB::connection('db_aux')->select($copy);
                         DB::commit();

                         return response()->json([
                             'message' => 'Realizado con éxito',
                             'payload' => [
                                 'successfully' => true,
                                'data_count' =>  $this->data_count_payroll_senasir($month,$year)
                             ],
                         ]);
                     } else {
                            return response()->json([
                             'message' => 'Error en el copiado del archivo',
                             'payload' => [
                                 'successfully' => false,
                                 'error' => 'El nombre del archivo no coincide con en nombre requerido'
                             ],
                         ]);
                     }
            } else {
                     return response()->json([
                         'message' => 'Error en el copiado del archivo',
                         'payload' => [
                             'successfully' => false,
                             'error' => 'El archivo no es un archivo CSV'
                         ],
                     ]);
             }
         }catch(Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error en el copiado de datos',
                'payload' => [
                    'successfully' => false,
                    'error' => $e->getMessage(),
                ],
            ]);
         }
     }

     /**
     * @OA\Post(
     *      path="/api/contribution/validation_payroll_senasir",
     *      tags={"IMPORTACION-PLANILLA-SENASIR"},
     *      summary="PASO 2 VALIDACION DE DATOS DE TITULARES SENASIR",
     *      operationId="validation_contribution_passives_affiliate_payroll_senasir",
     *      description="validacion de datos de titulares senasir a la tabla validation_contribution_passives_affiliate_payroll_senasir",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "2021-10-01")
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

    public function validation_payroll_senasir(Request $request){
        $request->validate([
        'date_payroll' => 'required|date_format:"Y-m-d"',
        ]);
        try{
                DB::beginTransaction();
                $message = "No hay datos";
                $successfully =false;
                $date_payroll_format = $request->date_payroll;
                $date_payroll = Carbon::parse($request->date_payroll);
                $year = (int)$date_payroll->format("Y");
                $month = (int)$date_payroll->format("m");
                $last_date = Carbon::parse($year.'-'.$month)->toDateString();
                $num_data_no_validated = 0;
                $connection_db_aux = Util::connection_db_aux();
    
                if($this->exists_data_payroll_copy_senasirs($month,$year)){
                    if(!PayrollSenasir::data_period($month,$year)['exist_data']){
    
                        $query = "select registration_payroll_senasir('$connection_db_aux','$month','$year');";
                        $data_validated = DB::select($query);
    
                            if($data_validated[0]->registration_payroll_senasir > 0){
                                $message = "Realizado con exito";
                                $successfully = true;
                                $data_payroll_copy_senasir = "select  * from  payroll_copy_senasirs  where mes ='$month' and a_o='$year' and is_validated = false and clase_renta not like 'ORFANDAD%'";
                                $data_payroll_copy_senasir = DB::connection('db_aux')->select($data_payroll_copy_senasir);
                                if(count($data_payroll_copy_senasir)> 0){
                                    $message = "Excel";                            
                                }
                            }
                        DB::commit();
                        $data_count = $this->data_count_payroll_senasir($month,$year);
                        return response()->json([
                            'message' => $message,
                            'payload' => [
                                'successfully' => $successfully,
                                'data_count' =>  $data_count
                            ],
                        ]);
                    }else{
                        return response()->json([
                            'message' => " Error! ya realizó la validación de datos",
                            'payload' => [
                                'successfully' => $successfully,
                                'error' => 'Error! ya realizó la validación de datos.'
                            ],
                        ]);
                    }
    
                }else{
                    return response()->json([
                        'message' => "Error no existen datos en la tabla del copiado de datos",
                        'payload' => [
                            'successfully' => $successfully,
                            'error' => 'Error el primer paso no esta concluido.'
                        ],
                    ]);
                }
    
            }catch(Exception $e){
                DB::rollBack();
                return response()->json([
                'message' => 'Error en la busqueda de datos de titulares.',
                'payload' => [
                    'successfully' => false,
                    'error' => $e->getMessage(),
                ],
                ]);
            }
        }
             /**
         * @OA\Post(
         *      path="/api/contribution/download_fail_not_found_payroll_senasir",
         *      tags={"IMPORTACION-PLANILLA-SENASIR"},
         *      summary="DESCARGA DE ARCHIVO DE NO ENCONTRADOS DEL PASO 2 DE VALIDACION DE DATOS AFILIADO TITULAR ",
         *      operationId="download_fail_not_found_payroll_senasir",
         *      description="Descarga el archivo de falla del paso 2 de validacion de datos del afiliado titular",
         *      @OA\RequestBody(
         *          description= "Provide auth credentials",
         *          required=true,
         *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
         *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "2021-10-01")
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
            public function download_fail_not_found_payroll_senasir(Request $request){
    
                $request->validate([
                    'date_payroll' => 'required|date_format:"Y-m-d"',
                ]);
                DB::beginTransaction();
                $message = "No hay datos";
                $data_cabeceras=array(array("ID_PERSONA_TITULAR","AÑO","MES","MATRÍCULA TITULAR", "MATRÍCULA D_H","DEPARTAMENTO","CARNET", "APELLIDO PATERNO","APELLIDO MATERNO", "PRIMER NOMBRE","SEGUNDO NOMBRE",
                "FECHA DE NACIMIENTO","CLASE DE RENTA","TOTAL GANADO","LIQUIDO PAGABLE","RENTA DIGNIDAD","DESCUENTO MUSERPOL","PATERNO TITULAR","MATERNO TITULAR"," PRIMER NOMBRE TITULAR",
                "SEGUNDO NOMBRE TITULAR","CARNET TITULAR","FECHA DE FALLECIMIENTO TITULAR"));
                $date_payroll = Carbon::parse($request->date_payroll);
                $year = (int)$date_payroll->format("Y");
                $month = (int)$date_payroll->format("m");
                $data_payroll_copy_senasir = "select  * from  payroll_copy_senasirs  where mes ='$month' and a_o='$year' and is_validated = false and clase_renta not like 'ORFANDAD%'";
                $data_payroll_copy_senasir = DB::connection('db_aux')->select($data_payroll_copy_senasir);
                if(count($data_payroll_copy_senasir)> 0){
                    $message = "Excel";
                    foreach ($data_payroll_copy_senasir as $row){
                        array_push($data_cabeceras, array($row->id_person_titular, $row->a_o ,$row->mes ,$row->matricula_titular ,$row->mat_dh ,
                        $row->departamento ,$row->carnet ,$row->paterno , $row->materno , $row->p_nombre ,$row->s_nombre ,
                        $row->fecha_nacimiento , $row->clase_renta , $row->total_ganado , $row->liquido_pagable , $row->renta_dignidad , $row->renta_dignidad ,
                        $row->pat_titular , $row->mat_titular , $row->p_nom_titular , $row->s_nombre_titular , $row->carnet_tit , $row->fec_fail_tit 
                    ));
                    }
    
                    $export = new ArchivoPrimarioExport($data_cabeceras);
                    $file_name = "no-encontrados-planilla-senasir";
                    $extension = '.xls';
                    return Excel::download($export, $file_name."_".$month."_".$year.$extension);
                }else{
                    return abort(403, 'No existe archivo de falla senasir para mostrar');
                }        
            }        
        // -------------metodo para verificar si existe datos en el paso 1 -----//
        public function exists_data_payroll_copy_senasirs($month,$year){
            $exists_data = true;
            $query = "select * from payroll_copy_senasirs where mes = $month::INTEGER and a_o = $year::INTEGER;";
            $verify_data = DB::connection('db_aux')->select($query);
    
            if($verify_data == []) $exists_data = false;
    
            return $exists_data;
        }
    
         //-----------borrado de datos de la tabla payroll_senasirs paso 2
         public function delete_payroll_senasirs($month, $year)
         {
                 if(PayrollSenasir::data_period($month,$year)['exist_data'])
                 {
                    $query = "delete
                            from payroll_senasirs
                            where year_p = $year::INTEGER and month_p = $month::INTEGER ";
                    $query = DB::select($query);
                    DB::commit();
                    return true;
                 }
                 else
                     return false;
         }
    
         //------------borrado de datos de la tabla payroll_copy_senasirs paso 1
         public function delete_payroll_copy_senasirs($month, $year)
         {
                 if($this->exists_data_payroll_copy_senasirs($month,$year))
                 {
                    $query = "delete
                            from payroll_copy_senasirs
                            where a_o = $year::INTEGER and mes = $month::INTEGER ";
                    $query = DB::connection('db_aux')->select($query);
                    DB::commit();
                    return true;
                 }
                 else
                     return false;
         }


    

    /**
     * @OA\Post(
     *      path="/api/contribution/rollback_payroll_copy_senasir",
     *      tags={"IMPORTACION-PLANILLA-SENASIR"},
     *      summary="REHACER PASO 1 IMPORTACION PLANILLA DE SENASIR",
     *      operationId="rollback_payroll_copy_senasir",
     *      description="Para rehacer paso 1 de la importación de planilla de senasir",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "2021-10-01")
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

     public function rollback_payroll_copy_senasir(Request $request)
     {
        $request->validate([
            'date_payroll' => 'required|date_format:"Y-m-d"',
          ]);
        DB::beginTransaction();
        try{
            $result['delete_step_1'] = false;
            $valid_rollback = false;
            $date_payroll = Carbon::parse($request->date_payroll);

            $year = (int)$date_payroll->format("Y");
            $month = (int)$date_payroll->format("m");

            if($this->exists_data_payroll_copy_senasirs($month,$year) && !PayrollSenasir::data_period($month,$year)['exist_data']){
                $result['delete_step_1'] = $this->delete_payroll_copy_senasirs($month,$year);

                if($result['delete_step_1'] == true){
                    $valid_rollback = true;
                    $message = "Realizado con éxito!";
                }
            }else{
                if(PayrollSenasir::data_period($month,$year)['exist_data'])
                    $message = "No se puede rehacer, por que ya realizó la validacion del la planilla senasir";
                else
                    $message = "No existen datos para rehacer";
            }

            DB::commit();

            return response()->json([
                'message' => $message,
                'payload' => [
                    'valid_rollbackk' =>  $valid_rollback,
                    'delete_step' =>  $result
                ],
            ]);
        }catch (Exception $e)
        {
            DB::rollback();
            return $e;
        }
     }
     /**
     * @OA\Post(
     *      path="/api/contribution/import_payroll_senasir_progress_bar",
     *      tags={"IMPORTACION-PLANILLA-SENASIR"},
     *      summary="INFORMACION DE PROGRESO DE IMPORTACION PLANILLA SENASIR",
     *      operationId="import_payroll_senasir_progress_bar",
     *      description="Muestra la informacion de la importación de senasir  (-1)Si exixtio al gun error en algun paso, (100)Si todo fue exitoso, (30-60)Paso 1 y 2 (0)si esta iniciando la importación",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "2021-10-01")
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

    public function  import_payroll_senasir_progress_bar(Request $request){

        $request->validate([
            'date_payroll' => 'required|date_format:"Y-m-d"',
          ]);

        $date_payroll = Carbon::parse($request->date_payroll);
        $year = (int)$date_payroll->format("Y");
        $month = (int)$date_payroll->format("m");
        $message = "Exito";

        $result['file_exists'] = false;
        $result['file_name'] = "";
        $result['percentage'] = 0;
        $result['query_step_1'] = false;
        $result['query_step_2'] = false;

        $result['query_step_1'] = $this->exists_data_payroll_copy_senasirs($month,$year);
        $result['query_step_2'] = PayrollSenasir::data_period($month,$year)['exist_data'];
        $date_payroll_format = $request->date_payroll;

        //verificamos si existe el el archivo de importación 
        $date_month= strlen($month)==1?'0'.$month:$month;
        $origin_name = 'senasir-';
        $new_file_name = "senasir-".$date_month."-".$year.'.csv';
        $base_path = 'planillas/planilla_senasir'.'/'.$new_file_name;
        if (Storage::disk('ftp')->has($base_path)) {
            $result['file_name'] = $new_file_name;
            $result['file_exists'] = true;
        }

        if($result['file_exists'] == true && $result['query_step_1'] == true && $result['query_step_2'] == true){
            $result['percentage'] = 100;
        }else{
            if($result['file_exists'] == true && $result['query_step_1'] == true && $result['query_step_2'] == false){
                $result['percentage'] = 50;
            }else{
                if ($result['query_step_1'] == false && $result['query_step_2'] == false) {
                    $result['percentage'] = 0;
                } else {
                    $result['percentage'] = -1;
                    $message = "Error! Algo salio mal en algun paso, favor vuelva a iniciar la importación.";
                }
            }
        }

        return response()->json([
            'message' => $message,
            'payload' => [
                'import_progress_bar' =>  $result,
                'data_count' =>  $this->data_count_payroll_senasir($month,$year)
            ],
        ]);
    }
/**
     * @OA\Post(
     *      path="/api/contribution/list_months_validate_senasir",
     *      tags={"IMPORTACION-PLANILLA-SENASIR"},
     *      summary="LISTA LOS MESES QUE SE REALIZARON IMPORTACIONES PLANILLA DE TIPO SENASIR EN BASE A UN AÑO DADO EJ:2021",
     *      operationId="list_months_validate_senasir",
     *      description="Lista los meses importados en la tabla payroll_copy_senasirs enviando como parametro un año en especifico",
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
    public function list_months_validate_senasir(Request $request)
    {
       $request->validate([
           'period_year' => 'required|date_format:"Y"',
           'with_data_count'=>'boolean'
       ]);
       $with_data_count = !isset($request->with_data_count) || is_null($request->with_data_count)? true:$request->with_data_count;
        $period_year = $request->get('period_year');
        $query = "SELECT  distinct month_p,year_p,  to_char( (to_date(year_p|| '-' ||month_p, 'YYYY/MM/DD')), 'TMMonth') as period_month_name from payroll_senasirs where deleted_at  is null and year_p =$period_year group by month_p, year_p";
        $query = DB::select($query);
        $query_months = "select id as period_month ,name  as period_month_name from months order by id asc";
        $query_months = DB::select($query_months);

        foreach ($query_months as $month) {
           $month->state_importation = false;
           foreach ($query as $month_payroll) {
               if($month->period_month == $month_payroll->month_p){
                   $month->state_importation = true;
                   break;
               }
           }
           $date_payroll_format = Carbon::parse($period_year.'-'.$month->period_month.'-'.'01')->toDateString();
           if($with_data_count)
           $month->data_count = $this->data_count_payroll_senasir($month->period_month,$period_year,$date_payroll_format);
        }

        return response()->json([
           'message' => "Exito",
           'payload' => [
               'list_months' =>  $query_months,
               'count_months' =>  count($query)
           ],
       ]);
    }
    public function data_count_payroll_senasir($mes,$a_o){
        $month = $mes;
        $year = $a_o;
        $data_count['num_total_data_copy'] = 0;
        $data_count['num_data_not_considered'] = 0;
        $data_count['num_data_considered'] = 0;
        $data_count['num_data_validated'] = 0;
        $data_count['num_data_not_validated'] = 0;

        //---TOTAL DE DATOS DEL ARCHIVO
        $query_total_data = "SELECT count(a_o) FROM payroll_copy_senasirs where mes = $month::INTEGER and a_o = $year::INTEGER;";
        $query_total_data = DB::connection('db_aux')->select($query_total_data);
        $data_count['num_total_data_copy'] = $query_total_data[0]->count;

        //---NUMERO DE DATOS NO CONSIDERADOS
        $query_data_not_considered = "SELECT count(a_o) FROM payroll_copy_senasirs where mes = $month::INTEGER and a_o = $year::INTEGER and clase_renta like 'ORFANDAD%';";
        $query_data_not_considered = DB::connection('db_aux')->select($query_data_not_considered);
        $data_count['num_data_not_considered'] = $query_data_not_considered[0]->count;

        //---NUMERO DE DATOS CONSIDERADOS
        $query_data_considered = "SELECT count(a_o) FROM payroll_copy_senasirs where mes = $month::INTEGER and a_o = $year::INTEGER and clase_renta not like 'ORFANDAD%';";
        $query_data_considered = DB::connection('db_aux')->select($query_data_considered);
        $data_count['num_data_considered'] = $query_data_considered[0]->count;

        //---NUMERO DE DATOS VALIDADOS
        $data_count['num_data_validated'] = PayrollSenasir::data_period($month,$year)['count_data'];
         //---NUMERO DE DATOS NO VALIDADOS
        $data_count['num_data_not_validated'] = $data_count['num_data_considered'] - $data_count['num_data_validated'];

        return  $data_count;
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/report_payroll_senasir",
     *      tags={"IMPORTACION-PLANILLA-SENASIR"},
     *      summary="GENERA REPORTE EXCEL DE DATOS REMITIDOS POR SENASIR",
     *      operationId="report_import_senasir ",
     *      description="Genera el archivo excel de los datos remitidos por el SENASIR por mes y año",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "2021-10-01")
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
   
    public function report_payroll_senasir(request $request) {

        $request->validate([
            'date_payroll' => 'required|date_format:"Y-m-d"',
        ]);

        DB::beginTransaction();
        $message = "No hay datos";
        $date_payroll_format = $request->date_payroll;
        $data_cabeceras=array(array("ID_AFILIADO","ID_PERSONA_SENASIR","AÑO","MES","MATRÍCULA TITULAR","MATRÍCULA D_H","DEPARTAMENTO","RENTA","CARNET", 
        "APELLIDO PATERNO","APELLIDO MATERNO","PRIMER NOMBRE","SEGUNDO NOMBRE","AP_CASADA","FECHA DE NACIMIENTO","CLASE DE RENTA","TOTAL GANADO",
        "TOTAL DESCUENTOS","LIQUIDO PAGABLE","REINTEGRO RENTA BASICA","RENTA DIGNIDAD","REINTEGRO RENTA DIGNIDAD","REINTEGRO AGUINALDO",
        "REINTEGRO IMPORTE ADICIONAL","REINTEGRO INCREMENTO DE GESTION","DESCUENTO MUSERPOL","DESCUENTO COVIPOL","DESCUENTO PRESTAMO MUSERPOL",
        "CARNET TITULAR","PATERNO TITULAR","MATERNO TITULAR","PRIMER NOMBRE TITULAR","SEGUNDO NOMBRE TITULAR",
        "APELLIDO CASADA TITULAR","FECHA DE NACIMIENTO TITULAR","CLASE DE RENTA TITULAR","FECHA FALLECIMIENTO TITULAR"));

        $date_payroll = Carbon::parse($request->date_payroll);
        $year = (int)$date_payroll->format("Y");
        $month = (int)$date_payroll->format("m");
        $data_payroll_senasir = "select  * from  payroll_senasirs  where month_p ='$month' and year_p='$year'";
                    $data_payroll_senasir = DB::select($data_payroll_senasir);
                            if(count($data_payroll_senasir)> 0){
                                $message = "Excel";
                                foreach ($data_payroll_senasir as $row){
                                    array_push($data_cabeceras, array($row->affiliate_id, $row->id_person_senasir, $row->year_p ,$row->month_p ,$row->registration_a ,$row->registration_s ,
                                    $row->department, $row->rent, $row->identity_card, $row->last_name , $row->mothers_last_name, $row->first_name, $row->second_name, $row->surname_husband,
                                    $row->birth_date, $row->rent_class, $row->total_won, $row->total_discounts, $row->payable_liquid, $row->refund_r_basic, $row->dignity_rent, $row->refund_dignity_rent,
                                    $row->refund_bonus, $row->refund_additional_amount, $row->refund_inc_management, $row->discount_contribution_muserpol, $row->discount_covipol, $row->discount_loan_muserpol, $row->identity_card_a,
                                    $row->last_name_a, $row->mothers_last_name_a , $row->first_name_a , $row->second_name_a , $row->surname_husband_a, $row->birth_date_a, $row->rent_class_a, $row->date_death_a
                                ));
                                }

                                $export = new ArchivoPrimarioExport($data_cabeceras);
                                $file_name = "Planilla_Senasir";
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
