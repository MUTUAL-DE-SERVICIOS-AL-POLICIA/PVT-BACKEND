<?php

namespace App\Http\Controllers\Contribution;

use App\Exports\ArchivoPrimarioExport;
use App\Helpers\Util;
use App\Http\Controllers\Controller;
use App\Models\Contribution\PayrollRegional;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportPayrollRegionalController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/contribution/upload_copy_payroll_regional",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="PASO 1 COPIADO DE DATOS PLANILLA REGIONAL",
     *      operationId="upload_copy_payroll_regional",
     *      description="Copiado de datos del archivo de planillas regional a la tabla payroll_copy_regionals",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="file", type="file", description="file required", example="file"),
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

    public function upload_copy_payroll_regional(request $request)
    {
        $extension = strtolower($request->file->getClientOriginalExtension());
        $file_name_entry = $request->file->getClientOriginalName();

        $route = '';
        $route_file_name = '';
        DB::beginTransaction();
        try{
            $username = env('FTP_USERNAME');
            $password = env('FTP_PASSWORD');
            $successfully = false;
            if($extension == "csv"){
                $rollback_period = "delete from payroll_copy_regionals";
                $rollback_period  = DB::connection('db_aux')->select($rollback_period);
                $file_name = "regional".'.'.$extension;
                    if($file_name_entry == $file_name){

                        $base_path = 'planillas/planilla_regional/'.Carbon::now()->toDateString();
                        $file_path = Storage::disk('ftp')->putFileAs($base_path,$request->file,$file_name);
                        $base_path ='ftp://'.env('FTP_HOST').env('FTP_ROOT').$file_path;                 

                        $drop = "drop table if exists payroll_copy_regional_tmp";
                        $drop = DB::connection('db_aux')->select($drop);

                        $temporary_payroll = "create temporary table payroll_copy_regional_tmp(
                                                carnet varchar,
                                                tipo_aportante varchar,
                                                nom varchar,
                                                nom2 varchar,
                                                pat varchar,
                                                mat varchar,
                                                ap_casada varchar,
                                                recibo varchar,
                                                fecha_deposito date,
                                                total_depositado decimal(13,2),
                                                mes integer,  
                                                a_o integer,
                                                total_pension decimal(13,2),
                                                renta_dignidad decimal(13,2),
                                                cotizable decimal(13,2),
                                                aporte decimal(13,2),    
                                                porcentaje_aporte decimal(13,2),
                                                affiliate_id_frcam integer)";

                        $temporary_payroll = DB::connection('db_aux')->select($temporary_payroll);
             
                        $copy = "copy payroll_copy_regional_tmp(carnet, tipo_aportante, nom, nom2, pat, mat, ap_casada, recibo, fecha_deposito, total_depositado, mes, a_o, total_pension, renta_dignidad, cotizable, aporte, porcentaje_aporte, affiliate_id_frcam)
                                FROM PROGRAM 'wget -q -O - $@  --user=$username --password=$password $base_path'
                                WITH DELIMITER ':' CSV header;";
                        $copy = DB::connection('db_aux')->select($copy);

                        // ******validación de datos****************
                        $verify_number_records = "select count(*) from payroll_copy_regional_tmp";
                        $verify_number_records = DB::connection('db_aux')->select($verify_number_records);

                        $verify_data = "select count(*) from payroll_copy_regional_tmp where a_o is null or mes is null;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        if($verify_data[0]->count > 0){
                            return response()->json([
                                'message' => 'Error en el copiado de datos',
                                'payload' => [
                                    'successfully' => false,
                                    'error' => 'Existen datos incorrectos en la(s) columnas de mes o año.',
                                    'route' => $route,
                                    'route_file_name' => $route_file_name
                                ],
                            ]);
                        }

                        // ****************************************
                        $insert = "INSERT INTO payroll_copy_regionals
                        (carnet, tipo_aportante, nom, nom2, pat, mat, ap_casada, recibo, fecha_deposito, total_depositado, mes, a_o, total_pension, renta_dignidad, cotizable, aporte, porcentaje_aporte, affiliate_id_frcam, created_at, updated_at)
                        SELECT carnet, tipo_aportante, nom, nom2, pat, mat, ap_casada, recibo, fecha_deposito::DATE, total_depositado::DECIMAL(13,2), mes::INTEGER, a_o::INTEGER, total_pension::DECIMAL(13,2), renta_dignidad::DECIMAL(13,2), 
                        cotizable::DECIMAL(13,2), aporte::DECIMAL(13,2), porcentaje_aporte::DECIMAL(13,2), affiliate_id_frcam::INTEGER, current_timestamp , current_timestamp 
                        FROM payroll_copy_regional_tmp";
                        $insert = DB::connection('db_aux')->select($insert);
                        
                        $drop = "drop table if exists payroll_copy_regional_tmp";
                        $drop = DB::connection('db_aux')->select($drop);

                        $data_count = $this->data_count_payroll_regional();                       

                        // *******Limpieza de duplicados en carnet**********
                        $data_cleaning = "WITH duplicados AS (
                            SELECT id,
                                    ROW_NUMBER() OVER (
                                        PARTITION BY carnet, a_o, mes, aporte
                                        ORDER BY id
                                    ) AS rn
                                FROM payroll_copy_regionals
                            )
                            UPDATE payroll_copy_regionals
                            SET deleted_at = NOW()
                            WHERE id IN (
                                SELECT id FROM duplicados WHERE rn > 1
                        );";
                        $data_cleaning = DB::connection('db_aux')->select($data_cleaning);

                        // ******validación de datos****************
                        $verify_data = "update payroll_copy_regionals pcr set error_message = concat(error_message,' - ','Los valores de los apellidos son NULOS ') from (select id from payroll_copy_regionals where a_o is null and mes is null and pat is null and mat is null and deleted_at is null) as subquery where pcr.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "update payroll_copy_regionals pcr set error_message = concat(error_message,' - ','El valor del primer nombre es NULO ') from (select id from payroll_copy_regionals where nom is null and deleted_at is null) as subquery where pcr.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "update payroll_copy_regionals pcr set error_message = concat(error_message,' - ','El número de carnet es duplicado en el mismo periodo') from (select carnet, a_o, mes from payroll_copy_regionals where deleted_at is null group by carnet, a_o, mes having count(*) > 1) as subquery where pcr.carnet = subquery.carnet and pcr.a_o = subquery.a_o and pcr.mes = subquery.mes and deleted_at is null;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        // ****************************************
                        $verify_data = "select count(id) from payroll_copy_regionals pcr where error_message is not null and deleted_at is null;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        if($verify_data[0]->count > 0) {
                            $route = '/contribution/download_error_data_regional';
                            $route_file_name = 'datos_observados_archivo.xls';
                            return response()->json([
                                'message' => 'Excel',
                                'payload' => [
                                    'successfully' => false,
                                    'error' => 'Existen datos en el archivo que son incorrectos y no serán considerados en la importación, favor revisar.',
                                    'route' => $route,
                                    'route_file_name' => $route_file_name
                                ],
                            ]);
                        }
                        DB::commit();

                        if($data_count['num_total_data_copy'] > 0){
                            $message = "Realizado con éxito";
                            $successfully = true;
                        }
                        return response()->json([
                            'message' => $message,
                            'payload' => [
                                'successfully' => $successfully,
                                'data_count' => $data_count,
                                'route' => $route,
                                'route_file_name' => $route_file_name
                            ],
                        ]);
                    } else {
                           return response()->json([
                            'message' => 'Error en el copiado del archivo',
                            'payload' => [
                                'successfully' => $successfully,
                                'error' => 'El nombre del archivo no coincide con en nombre requerido',
                                'route' => $route,
                                'route_file_name' => $route_file_name
                            ],
                        ]);
                    }
            } else {
                    return response()->json([
                        'message' => 'Error en el copiado del archivo',
                        'payload' => [
                            'successfully' => $successfully,
                            'error' => 'El archivo no es un archivo CSV',
                            'route' => $route,
                            'route_file_name' => $route_file_name
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
                   'route' => $route,
                   'route_file_name' => $route_file_name
               ],
           ]);
        }
    }

    public function data_count_payroll_regional(){
        $data_count['num_total_data_copy'] = 0;
        $data_count['num_data_not_considered'] = 0;
        $data_count['num_data_unrelated'] = 0;
        $data_count['num_data_considered'] = 0;
        $data_count['num_data_validated'] = 0;
        $data_count['num_data_not_validated'] = 0;

        // ---TOTAL DE DATOS DEL ARCHIVO
        $query_total_data = "SELECT count(id) FROM payroll_copy_regionals;";
        $query_total_data = DB::connection('db_aux')->select($query_total_data);
        $data_count['num_total_data_copy'] = $query_total_data[0]->count;

        // ---NUMERO DE DATOS NO CONSIDERADOS duplicados de afiliados y aportes
        $query_data_not_considered = "SELECT count(id) FROM payroll_copy_regionals where error_message is not null or deleted_at is not null;";
        $query_data_not_considered = DB::connection('db_aux')->select($query_data_not_considered);
        $data_count['num_data_not_considered'] = $query_data_not_considered[0]->count;

        // ---NUMERO DE DATOS NO RELACIONADOS 
        $query_data_unrelated= "SELECT count(id) FROM payroll_copy_regionals where error_message is null and deleted_at is null and criteria = '9-no-identificado';";
        $query_data_unrelated= DB::connection('db_aux')->select($query_data_unrelated);
        $data_count['num_data_unrelated'] = $query_data_unrelated[0]->count;

        // ---NUMERO DE DATOS CONSIDERADOS 
        $query_data_considered = "SELECT count(id) FROM payroll_copy_regionals where error_message is null and deleted_at is null;";
        $query_data_considered = DB::connection('db_aux')->select($query_data_considered);
        $data_count['num_data_considered'] = $query_data_considered[0]->count;

        // ---NUMERO DE DATOS VALIDADOS
        $data_count['num_data_validated'] = PayrollRegional::data_period()['count_data'];

        return  $data_count;
    }

    /**
      * @OA\Post(
      *      path="/api/contribution/download_error_data_regional",
      *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
      *      summary="DESCARGA EL ARCHIVO, CON EL LISTADO DE AFILIADOS QUE TENGAN OBSERVACIONES EN EL ARCHIVO",
      *      operationId="download_error_data_regional",
      *      description="Descarga el archivo con el listado de afiliados con CI duplicado, primer nombre nulo, apellido paterno y materno en nulo",
      *      security={
      *         {"bearerAuth": {}}
      *      },
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
    public function download_error_data_regional(Request $request){

        $data_header=array(array("AÑO","MES","CARNET","APELLIDO PATERNO","APELLIDO MATERNO","APELLIDO CASADA","PRIMER NOMBRE","SEGUNDO NOMBRE","APORTE","OBSERVACIÓN"));

        $data_payroll_copy_regional = "select a_o,mes,carnet,pat,mat,ap_casada,nom,nom2,aporte,error_message from payroll_copy_regionals pcr where error_message is not null or deleted_at is not null order by carnet";
        $data_payroll_copy_regional = DB::connection('db_aux')->select($data_payroll_copy_regional);
            foreach ($data_payroll_copy_regional as $row){
                array_push($data_header, array($row->a_o,$row->mes,$row->carnet,$row->pat,
                $row->mat,$row->ap_casada,$row->nom,$row->nom2,$row->aporte,$row->error_message));
            }
            $export = new ArchivoPrimarioExport($data_header);
            $file_name = "observacion-planilla-regional";
            $extension = '.xls';
            return Excel::download($export, $file_name."_".$extension);
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/validation_affiliate_regional",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="PASO 2 VALIDACIÓN AFILIADOS Y APORTES",
     *      operationId="validation_affiliate_regional",
     *      description="Validación de afiliados y aportes de la planilla regionales",
     *      security={
     *         {"bearerAuth": {}}
     *      },
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
    public function validation_affiliate_regional(Request $request){
        try{
            DB::beginTransaction();
            $message = "No hay datos por validar";
            $successfully =false;
            $data_count['num_total_data_copy'] = 0;
            $data_count['count_data_automatic_link'] = 0;
            $data_count['count_data_unidentified'] = 0;
            $data_count['count_data_error'] = 0;
            $data_count['num_total_data_payroll'] = 0;
            $data_count['num_total_data_contribution'] = 0;
            $route = '';
            $route_file_name = '';

            $connection_db_aux = Util::connection_db_aux();
            $query = "select search_affiliate_regional('$connection_db_aux');";
            $data_validated = DB::select($query);
            $num_total_data_copy = $this->data_count_payroll_regional();
            $count_data_automatic_link = "select count(id) from payroll_copy_regionals pcr where criteria in ('1-CI-sPN-sPA-sSA', '2-partCI-sPN-sPA', '3-sCI-MAT-PN-PA', '4-MAT-APCAS', '5-cCI-sPN-sPA','6-partcCI-sPN-sPA')";
            $count_data_automatic_link = DB::connection('db_aux')->select($count_data_automatic_link);
            $count_data_unidentified = "select count(id) from payroll_copy_regionals pcr where criteria in ('9-no-identificado')";
            $count_data_unidentified = DB::connection('db_aux')->select($count_data_unidentified);
            $count_data_error = "select count(id) from payroll_copy_regionals pcr where error_message is not null or deleted_at is not null";
            $count_data_error = DB::connection('db_aux')->select($count_data_error);
            $data_count['num_total_data_copy'] = $num_total_data_copy['num_total_data_copy'];
            $data_count['count_data_automatic_link'] = $count_data_automatic_link[0]->count;
            $data_count['count_data_unidentified'] = $count_data_unidentified[0]->count;
            $data_count['count_data_error'] = $count_data_error[0]->count;

            $this->validation_contribution_regional();

            if($num_total_data_copy['num_total_data_copy'] <= 0){
                $successfully = false;
                $message = 'no existen datos';
            }elseif($count_data_unidentified[0]->count > 0){
                $successfully = false;
                $message = 'Excel';
                $route = '/contribution/download_data_revision';
                $route_file_name = 'observados_para_revision.xls';
            }elseif($count_data_unidentified[0]->count == 0 && $count_data_error[0]->count > 0){
                $valid_contribution = "select count(id) from payroll_copy_regionals pcr where state like 'accomplished' and error_message is not null";
                $valid_contribution = DB::connection('db_aux')->select($valid_contribution);
                if($valid_contribution[0]->count == 0){
                    $successfully =true;
                    $message = 'Excel';
                    $route = '/contribution/download_data_revision';
                    $route_file_name = 'afiliados_para_creacion.xls';
                }else{
                    $successfully =false;
                    $message = 'Excel';
                    $route = '/contribution/download_error_data_archive';
                    $route_file_name = 'datos_aportes_observados.xls';
                }
            }elseif($count_data_unidentified[0]->count == 0 && $count_data_error[0]->count == 0){
                $successfully =true;
                $message = 'Realizado con éxito.';
            }else{
                $successfully =false;
                $message = 'Ops ocurrió algo inesperado.';
            }
            return response()->json([
                'message' => $message,
                'payload' => [
                    'successfully' => $successfully,
                    'data_count' => $data_count,
                    'route' => $route,
                    'route_file_name' => $route_file_name
                ],
            ]);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json([
            'message' => 'Error en la búsqueda de datos de afiliados.',
            'payload' => [
                'successfully' => false,
                'error' => $e->getMessage(),
            ],
            ]);
        }
    }
    
    /**
    * @OA\Post(
    *      path="/api/contribution/copy_affiliate_id_regional",
    *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
    *      summary="PASO 2.1 COPIA DE NUPS NO IDENTIFICADOS POR CRITERIO CON LOS IDENTIFICADOS POR FRCAM",
    *      operationId="copy_affiliate_id_regional",
    *      description="copia de affiliate_id_frcam a affiliate_id regional",
    *      @OA\RequestBody(
    *          description= "Provide auth credentials",
    *          required=false,
    *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
    *             @OA\Property()
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

    public function copy_affiliate_id_reg_frcam_to_affiliate_id(request $request){

        $query = "UPDATE payroll_copy_regionals pcr
                   SET affiliate_id = pcr.affiliate_id_frcam,
                   criteria = '10-affiliate_id_frcam'
                   WHERE pcr.affiliate_id IS NULL AND pcr.affiliate_id_frcam IS NOT NULL;";
        DB::connection('db_aux')->select($query);
        $data_count = $this->data_count_payroll_regional(); 
        return response()->json([
            'payload' => [
                'data_count' => $data_count
            ],
        ]);
    }


    // Método para verificar si existe montos con diferentes contribuciones
    public function validation_contribution_regional(){
        $different_contribution = false;

        $connection_db_aux = Util::connection_db_aux();
        //1. Reemplaza los casos que tengan aportes iguales registrados desde la planilla de regionales
        //2. Reemplaza los valores que contengan cero en aporte aunque estén clasificados
        $payroll_regional =  DB::select("SELECT pcr.id, cp.affiliate_id, pcr.aporte, cp.total, cp.contribution_type_mortuary_id
        FROM contribution_passives cp
        JOIN dblink('$connection_db_aux', 'SELECT id, affiliate_id, a_o, mes, aporte FROM payroll_copy_regionals')
        AS pcr(id INT, affiliate_id INT, a_o INT, mes INT, aporte NUMERIC(13,2)) ON cp.affiliate_id = pcr.affiliate_id
        where EXTRACT(YEAR FROM cp.month_year) = pcr.a_o 
        AND EXTRACT(MONTH FROM cp.month_year) = pcr.mes 
        AND cp.total <> pcr.aporte
        AND cp.total > 0");

          foreach($payroll_regional as $update_payroll) {
            $messages = [];
             if ($update_payroll->total != $update_payroll->aporte) {
                $messages[] = "La contribución anterior es: $update_payroll->total difiere de la planilla $update_payroll->aporte";
            }
            if (!empty($messages)) {
                $error_message = implode(' - ', $messages);
                $update_query = "
                    UPDATE payroll_copy_regionals pcr 
                    SET error_message = CONCAT(COALESCE(error_message, ''), ' - ', '$error_message') 
                    WHERE pcr.id = $update_payroll->id;
                ";
                $update_query = DB::connection('db_aux')->select($update_query);
                $different_contribution = true;
            }
        }
          if($different_contribution == true){
            return false;
        }else{
            return true;
        }
    }
    
    /**
     * @OA\Post(
     *      path="/api/contribution/import_payroll_regional",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="PASO 3 VALIDACIÓN DE DATOS APORTES",
     *      operationId="validation_contribution_regional",
     *      description="Validación de datos de aportes de payroll_copy_regionals a la tabla payroll_regionals",
     *      security={
     *         {"bearerAuth": {}}
     *      },
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
    public function import_payroll_regional(Request $request){
        try{
                DB::beginTransaction();
                $message = "No hay datos";
                $successfully =false;
                $connection_db_aux = Util::connection_db_aux();
    
                // Conteo de  affiliate_id is null distinto del criterio 9-no-identificado
                $count_data = "SELECT count(id) FROM payroll_copy_regionals where error_message is null and deleted_at is null and affiliate_id is null and criteria!='9-no-identificado';";
                $count_data = DB::connection('db_aux')->select($count_data);
                if($count_data[0]->count == 0){
                    $count_data_validated = "SELECT count(id) FROM payroll_copy_regionals where state ='validated';";
                    $count_data_validated = DB::connection('db_aux')->select($count_data_validated);

                    if($count_data_validated[0]->count == 0){
                        $query = "select registration_payroll_regionals('$connection_db_aux');";
                        $data_validated = DB::select($query);
                            if($data_validated){
                                $message = "Realizado con éxito";
                                $successfully = true;
                                $data_payroll_copy_regional = "select * from payroll_copy_regionals where state='validated';";
                                $data_payroll_copy_regional = DB::connection('db_aux')->select($data_payroll_copy_regional);
                                if(count($data_payroll_copy_regional)> 0){
                                    $message = "Excel";                            
                                }
                            }
                        DB::commit();
                        $data_count = $this->data_count_payroll_regional();
                        return response()->json([
                            'message' => $message,
                            'payload' => [
                                'successfully' => $successfully,
                                'data_count' =>  $data_count
                            ],
                        ]);
                    }else{
                        return response()->json([
                            'message' => " Error! ya realizó la validación de datos.",
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
                            'error' => 'Error el primer paso no esta concluido o se concluyó el 3er paso.'
                        ],
                    ]);
                }
    
            }catch(Exception $e){
                DB::rollBack();
                return response()->json([
                'message' => 'Ocurrió un error.',
                'payload' => [
                    'successfully' => false,
                    'error' => $e->getMessage(),
                ],
                ]);
            }
        }
    
    // Método para verificar si existe datos en el Paso 1
    public function exists_data_payroll_copy_regional(){
        $exists_data = true;
        $query = "SELECT * FROM payroll_copy_regionals WHERE NOT EXISTS ( SELECT 1 FROM payroll_copy_regionals WHERE state != 'accomplished');";
        $verify_data = DB::connection('db_aux')->select($query);

        if($verify_data == []) $exists_data = false;

        return $exists_data;
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/import_contribution_regional",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="PASO 4 IMPORTACIÓN DE CONTRIBUCIONES REGIONAL",
     *      operationId="import_contribution_regional",
     *      description="Importación de aportes de regional a la tabla contribution_passives",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object"
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
    public function import_contribution_regional(Request $request){
        try {
            DB::beginTransaction();
            $userId = Auth::id();
            $message = 'No existen datos de la planilla.';
            $success = false;
    
            // Verifica si ya se realizó una importación
            $existingContributions = DB::table('contribution_passives')
                ->where('contributionable_type', 'payroll_regionals')
                ->count();
    
            if ($existingContributions > 0) {
                return response()->json([
                    'message' => 'Error: ya se realizó la importación de datos.',
                    'payload' => [
                        'successfully' => false,
                        'num_total_data_contribution' => $existingContributions,
                    ],
                ]);
            }
    
            // Verifica si hay datos en payroll_regionals
            $payrollCount = DB::table('payroll_regionals')->count();
    
            if ($payrollCount > 0) {
                DB::select("SELECT import_contribution_regional(?)", [$userId]);
    
                DB::commit(); // Confirma transacción
    
                $message = '¡Importación realizada con éxito!';
                $success = true;
    
                $totalContributions = DB::table('contribution_passives')
                    ->where('contributionable_type', 'payroll_regionals')
                    ->count();
            } else {
                $totalContributions = 0;
            }
            return response()->json([
                'message' => $message,
                'payload' => [
                    'successfully' => $success,
                    'num_total_data_contribution' => $totalContributions,
                ],
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al realizar la importación.',
                'payload' => [
                    'successfully' => false,
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }
}
