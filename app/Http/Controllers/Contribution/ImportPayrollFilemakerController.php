<?php

namespace App\Http\Controllers\Contribution;

use App\Http\Controllers\Controller;
use App\Models\Contribution\PayrollFilemaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Helpers\Util;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArchivoPrimarioExport;
use App\Models\Contribution\PayrollTranscriptPeriod;
// use App\Models\Contribution\PayrollTranscript;
// use App\Models\Contribution\Contribution;
// use App\Models\Contribution\ContributionImportPeriod;
use Auth;

class ImportPayrollFilemakerController extends Controller
{
    
        /**
     * @OA\Post(
     *      path="/api/contribution/upload_copy_payroll_filemaker",
     *      tags={"IMPORTACION-PLANILLA-FILEMAKER"},
     *      summary="PASO 1 COPIADO DE DATOS PLANILLA FILEMAKER",
     *      operationId="upload_copy_payroll_filemaker",
     *      description="Copiado de datos del archivo de planillas filemaker a la tabla payroll_copy_filemaker",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="file", type="file", description="file required", example="file")
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

    public function upload_copy_payroll_filemaker(request $request)
    {
        $request->validate([
            'file' => 'required',


            // 'number_records' => 'required|integer',
            // 'total_amount' => 'required|numeric',
        ]);
        $extencion = strtolower($request->file->getClientOriginalExtension());
        $file_name_entry = $request->file->getClientOriginalName();


        $route = '';
        $route_file_name = '';
        DB::beginTransaction();
        try{

            $username = env('FTP_USERNAME');
            $password = env('FTP_PASSWORD');
            $successfully = false;
            if($extencion == "csv"){
                // $date_payroll = Carbon::parse($request->date_payroll);
                // $year = $date_payroll->format("Y");

                // $month = $date_payroll->format("m");
                // $month_format =(int)$month;

                $rollback_period = "delete from payroll_copy_filemaker";
                $rollback_period  = DB::connection('db_aux')->select($rollback_period);
                $file_name = "filemaker".'.'.$extencion;
                    if($file_name_entry == $file_name){

                        $base_path = 'planillas/planilla_filemaker/'.Carbon::now()->toDateString();
                        $file_path = Storage::disk('ftp')->putFileAs($base_path,$request->file,$file_name);
                        $base_path ='ftp://'.env('FTP_HOST').env('FTP_ROOT').$file_path;
                 

                        $drop = "drop table if exists payroll_copy_filemaker_tmp";
                        $drop = DB::connection('db_aux')->select($drop);

                        $temporary_payroll = "create temporary table payroll_copy_filemaker_tmp(nro integer, mes integer, a_o integer,
                        carnet varchar, matricula varchar, pat varchar, mat varchar, nom varchar, nom2 varchar, ap_casada varchar,
                        grado varchar, desc_mens decimal(13,2), class_rent varchar)";
                        $temporary_payroll = DB::connection('db_aux')->select($temporary_payroll);
             
                        $copy = "copy payroll_copy_filemaker_tmp(nro, mes, a_o, carnet, matricula, pat, mat, nom, nom2, ap_casada, grado, desc_mens, class_rent)
                                FROM PROGRAM 'wget -q -O - $@  --user=$username --password=$password $base_path'
                                WITH DELIMITER ':' CSV header;";
                        $copy = DB::connection('db_aux')->select($copy);

                        //******validación de datos****************/

                        $verify_number_records = "select count(*) from payroll_copy_filemaker_tmp";
                        $verify_number_records = DB::connection('db_aux')->select($verify_number_records);

                        // if($verify_number_records[0]->count !=  $request->number_records) {
                        //     return response()->json([
                        //         'message' => 'Error en el copiado de datos',
                        //         'payload' => [
                        //             'successfully' => false,
                        //             'error' => 'El total de registros ingresado no coincide con la cantidad de registros del archivo.',
                        //             'route' => $route,
                        //             'route_file_name' => $route_file_name
                        //         ],
                        //     ]);
                        // }

                        $verify_data = "select count(*) from payroll_copy_filemaker_tmp where mes is null or a_o is null;";
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

                        // $verify_amount = "select sum(mus) from payroll_copy_filemaker_tmp";
                        // $verify_amount = DB::connection('db_aux')->select($verify_amount);

                        // if($verify_amount[0]->sum !=  $request->total_amount) {
                        //     return response()->json([
                        //         'message' => 'Error en el copiado de datos',
                        //         'payload' => [
                        //             'successfully' => false,
                        //             'error' => 'El monto total ingresado no coincide con el monto total de la planilla, favor de verificar.'.$verify_amount[0]->sum . ' distinto a '.$request->total_amount,
                        //             'route' => $route,
                        //             'route_file_name' => $route_file_name
                        //         ],
                        //     ]);
                        // }
                        //****************************************/
                        
                        $insert = "INSERT INTO payroll_copy_filemaker(mes,a_o,carnet,matricula,pat,mat,nom,nom2,ap_casada,grado,desc_mens,class_rent,created_at,updated_at)
                                   SELECT mes::INTEGER,a_o::INTEGER,carnet,matricula,pat,mat,nom,nom2,ap_casada,grado,desc_mens,class_rent,current_timestamp,current_timestamp FROM payroll_copy_filemaker_tmp; ";
                        $insert = DB::connection('db_aux')->select($insert);

                        $drop = "drop table if exists payroll_copy_filemaker_tmp";
                        $drop = DB::connection('db_aux')->select($drop);

                        $data_count = $this->data_count_payroll_filemaker();

                        //*******Limpieza de duplicados en carnet, mes, a_o, desc_mens************//
                            $data_cleaning = "    WITH duplicados AS (
                                SELECT id,
                                    ROW_NUMBER() OVER (
                                        PARTITION BY carnet, mes, a_o, desc_mens
                                        ORDER BY id
                                    ) AS rn
                                FROM payroll_copy_filemaker
                            )
                            UPDATE payroll_copy_filemaker
                            SET deleted_at = NOW()
                            WHERE id IN (
                                SELECT id FROM duplicados WHERE rn > 1
                        );";
                        $data_cleaning = DB::connection('db_aux')->select($data_cleaning);

                        //******validación de datos****************/
                        $verify_data = "update payroll_copy_filemaker pt set error_messaje = concat(error_messaje,' - ','Los valores de los apellidos son NULOS ') from (select id from payroll_copy_filemaker where mes is null and a_o is null and pat is null and mat is null and deleted_at is null) as subquery where pt.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "update payroll_copy_filemaker pt set error_messaje = concat(error_messaje,' - ','El valor del primer nombre es NULO ') from (select id from payroll_copy_filemaker where  nom is null and deleted_at is null) as subquery where pt.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "update payroll_copy_filemaker pt set error_messaje = concat(error_messaje,' - ','El numero de carnet es duplicado ') from (select carnet, mes, a_o from payroll_copy_filemaker group by carnet, mes, a_o having count(*) > 1) as subquery where pt.carnet = subquery.carnet and pt.mes = subquery.mes and pt.a_o = subquery.a_o and deleted_at is null;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);


                        //****************************************/
                        $verify_data = "select count(id) from payroll_copy_filemaker pcf where error_messaje is not null and deleted_at is null;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        if($verify_data[0]->count > 0) {
                            $route = '/contribution/download_error_data_filemaker';
                            $route_file_name = 'datos_observados_archivo.xls';
                            return response()->json([
                                'message' => 'Excel',
                                'payload' => [
                                    'successfully' => false,
                                    'error' => 'Existen datos en el archivo que son incorrectos y no seran considerados en la importación, favor revisar.',
                                    'route' => $route,
                                    'route_file_name' => $route_file_name
                                ],
                            ]);
                        }
                        //****************************************/
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

    public function data_count_payroll_filemaker(){
        $data_count['num_total_data_copy'] = 0;
        $data_count['num_data_not_considered'] = 0;
        $data_count['num_data_unrelated'] = 0;
        $data_count['num_data_considered'] = 0;
        $data_count['num_data_validated'] = 0;
        $data_count['num_data_not_validated'] = 0;

        //---TOTAL DE DATOS DEL ARCHIVO
        $query_total_data = "SELECT count(id) FROM payroll_copy_filemaker ";
        $query_total_data = DB::connection('db_aux')->select($query_total_data);
        $data_count['num_total_data_copy'] = $query_total_data[0]->count;

        //---NUMERO DE DATOS NO CONSIDERADOS duplicados de afilaidos y aportes
        $query_data_not_considered = "SELECT count(id) FROM payroll_copy_filemaker where error_messaje is not null or deleted_at is not null;";
        $query_data_not_considered = DB::connection('db_aux')->select($query_data_not_considered);
        $data_count['num_data_not_considered'] = $query_data_not_considered[0]->count;

        //---NUMERO DE DATOS NO RELACIONADOS 
        $query_data_unrelated= "SELECT count(id) FROM payroll_copy_filemaker where error_messaje is null and deleted_at is null and criteria = '7-no-identificado';";
        $query_data_unrelated= DB::connection('db_aux')->select($query_data_unrelated);
        $data_count['num_data_unrelated'] = $query_data_unrelated[0]->count;

        //---NUMERO DE DATOS CONSIDERADOS 
        $query_data_considered = "SELECT count(id) FROM payroll_copy_filemaker where error_messaje is null and deleted_at is null;";
        $query_data_considered = DB::connection('db_aux')->select($query_data_considered);
        $data_count['num_data_considered'] = $query_data_considered[0]->count;

        //---NUMERO DE DATOS VALIDADOS
        $data_count['num_data_validated'] = PayrollFilemaker::data_period()['count_data'];
        //  //---NUMERO DE DATOS NO VALIDADOS
        // $data_count['num_data_not_validated'] = $data_count['num_data_considered'] - $data_count['num_data_validated'];

        return  $data_count;
    }

         /**
      * @OA\Post(
      *      path="/api/contribution/download_error_data_filemaker",
      *      tags={"IMPORTACION-PLANILLA-FILEMAKER"},
      *      summary="DESCARGA EL ARCHIVO, CON EL LISTADO DE AFILIADOS QUE TENGAN OBSERVACIONES EN EL ARCHIVO",
      *      operationId="download_error_data_filemaker",
      *      description="Descarga el archivo con el listado de afiliados con CI duplicado, primer nombre nulo, apellido paterno y materno en nulo ",
      *      @OA\RequestBody(
      *          description= "Provide auth credentials",
      *          required=true,
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
    public function download_error_data_filemaker(Request $request){

    $message = "No hay datos";
    $data_header=array(array("AÑO","MES","CARNET","APELLIDO PATERNO","APELLIDO MATERNO","PRIMER NOMBRE","SEGUNDO NOMBRE","APORTE","OBSERVACIÓN"));
    $date_payroll = Carbon::parse($request->date_payroll);

    $data_payroll_copy_filemaker = "select a_o,mes,carnet,pat,mat,nom,nom2,desc_mens,error_messaje from payroll_copy_filemaker pcf where error_messaje is not null or error_messaje ='' order by carnet";
    $data_payroll_copy_filemaker = DB::connection('db_aux')->select($data_payroll_copy_filemaker);
        foreach ($data_payroll_copy_filemaker as $row){
            array_push($data_header, array($row->a_o,$row->mes,$row->carnet,$row->pat,
            $row->mat,$row->nom,$row->nom2,$row->desc_mens,$row->error_messaje));
        }
        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "observacion-planilla-filemaker";
        $extension = '.xls';
        return Excel::download($export, $file_name."_".$extension);
    }

         /**
     * @OA\Post(
     *      path="/api/contribution/validation_affiliate_filemaker",
     *      tags={"IMPORTACION-PLANILLA-FILEMAKER"},
     *      summary="PASO 2 VALIDACION AFILIADOS Y APORTES",
     *      operationId="validation_affiliate_filemaker",
     *      description="validacion de Afiliados y aportres de la planilla filemaker",
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

    public function validation_affiliate_filemaker(Request $request){
        // $request->validate([
        // 'date_payroll' => 'required|date_format:"Y-m-d"',
        // ]);
        
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
            // $date_payroll_format = $request->date_payroll;
            // $date_payroll = Carbon::parse($request->date_payroll);
            // $year = (int)$date_payroll->format("Y");
            // $month = (int)$date_payroll->format("m");
            $connection_db_aux = Util::connection_db_aux();
            $query = "select search_affiliate_filemaker('$connection_db_aux');";
            $data_validated = DB::select($query);
            $num_total_data_copy = $this->data_count_payroll_filemaker();
            $count_data_automatic_link = "select count(id) from payroll_copy_filemaker pcf where criteria in ('1-CI-sPN-sPA-sSA', '2-partCI-sPN-sPA', '3-sCI-MAT-PN-PA', '4-MAT-APCAS', '5-cCI-sPN-sPA','6-partcCI-sPN-sPA')";
            $count_data_automatic_link = DB::connection('db_aux')->select($count_data_automatic_link);
            $count_data_unidentified = "select count(id) from payroll_copy_filemaker pcf where criteria in ('7-no-identificado')";
            $count_data_unidentified = DB::connection('db_aux')->select($count_data_unidentified);
            $count_data_error = "select count(id) from payroll_copy_filemaker pcf where error_messaje is not null or deleted_at is not null";
            $count_data_error = DB::connection('db_aux')->select($count_data_error);
            $data_count['num_total_data_copy'] = $num_total_data_copy['num_total_data_copy'];
            $data_count['count_data_automatic_link'] = $count_data_automatic_link[0]->count;
            $data_count['count_data_unidentified'] = $count_data_unidentified[0]->count;
            $data_count['count_data_error'] = $count_data_error[0]->count;
            $validated_contribution = $this->validation_contribution_filemaker();
             return $validated_contribution;
            if($num_total_data_copy['num_total_data_copy'] <= 0){
                $successfully =false;
                $message = 'no existen datos';
            }elseif($count_data_unidentified[0]->count > 0){
                $successfully =false;
                $message = 'Excel';
                $route = '/contribution/download_data_revision';
                $route_file_name = 'observados_para_revision.xls';
            }elseif($count_data_unidentified[0]->count == 0 && $count_data_error[0]->count > 0){
                $valid_contribution =  "select count(id) from payroll_copy_filemaker pcf  where state like 'accomplished' and error_messaje is not null";
                $valid_contribution = DB::connection('db_aux')->select($valid_contribution);
                if($valid_contribution[0]->count == 0){
                    $successfully =true;
                    $message = 'Excel';
                    $route = '/contribution/download_data_revision';
                    $route_file_name = 'afiliados_para creacion.xls';
                }else{
                    $successfully =false;
                    $message = 'Excel';
                    $route = '/contribution/download_error_data_archive';
                    $route_file_name = 'datos_aportes_observados.xls';
                }
            }elseif($count_data_unidentified[0]->count == 0 && $count_data_error[0]->count == 0){
                $successfully =true;
                $message = 'Realizado con Exito.';
            }else{
                $successfully =false;
                $message = 'Ops Ocurrio algo inesperado.';
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
            'message' => 'Error en la busqueda de datos de afiliados.',
            'payload' => [
                'successfully' => false,
                'error' => $e->getMessage(),
            ],
            ]);
        }
    }

    //método para verificar si existe montos con diferentes contribuciones
    public function validation_contribution_filemaker(){
        $different_contribution = false;
        //$date_payroll = Carbon::parse($date_payroll);
        //$year = (int)$date_payroll->format("Y");
        //$month = (int)$date_payroll->format("m");
        $connection_db_aux = Util::connection_db_aux();

        $payroll_filermaker =  DB::select("SELECT pcf.id, cp.affiliate_id, pcf.desc_mens, cp.total, cp.contribution_type_mortuary_id
        FROM contribution_passives cp
        JOIN dblink('$connection_db_aux', 'SELECT id, affiliate_id, a_o, mes, desc_mens FROM payroll_copy_filemaker')
        AS pcf(id INT, affiliate_id INT, a_o INT, mes INT, desc_mens NUMERIC(13,2)) ON cp.affiliate_id = pcf.affiliate_id
        where EXTRACT(YEAR FROM cp.month_year) = pcf.a_o AND EXTRACT(MONTH FROM cp.month_year) = pcf.mes AND (cp.total <> pcf.desc_mens OR cp.contribution_type_mortuary_id IS NOT NULL)");


        foreach($payroll_filermaker as $update_payroll) {
            $messages = [];

            if ($update_payroll->total != $update_payroll->desc_mens) {
                $messages[] = "La contribución anterior es: $update_payroll->total difiere de la planilla $update_payroll->desc_mens";
            }

            if (!is_null($update_payroll->contribution_type_mortuary_id)) {
                $messages[] = "tramite clasificado como $update_payroll->contribution_type_mortuary_id";
            }

            if (!empty($messages)) {
                $error_message = implode(' - ', $messages);
                $update_query = "
                    UPDATE payroll_copy_filemaker pf 
                    SET error_messaje = CONCAT(COALESCE(error_messaje, ''), ' - ', '$error_message') 
                    WHERE pf.id = $update_payroll->id;
                ";
                $update_query = DB::connection('db_aux')->select($update_query);
                // DB::connection('db_aux')->statement($update_query);
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
     *      path="/api/contribution/import_payroll_filemaker",
     *      tags={"IMPORTACION-PLANILLA-FILEMAKER"},
     *      summary="PASO 3 VALIDACION DE DATOS APORTES",
     *      operationId="validation_contribution_filemaker",
     *      description="validacion de datos de aportes de payrollcopi_filemaker a la tabla payroll_filemakers",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
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

    public function import_payroll_filemaker(Request $request){

        try{
                DB::beginTransaction();
                $message = "No hay datos";
                $successfully =false;
                $connection_db_aux = Util::connection_db_aux();
    
                //conteo de  affiliate_id is null distito del criterio 6-CREAR
                $count_data = "SELECT count(id) FROM payroll_copy_filemaker where error_messaje is null and deleted_at is null and affiliate_id is null and criteria!='7-no-identificado';";
                $count_data = DB::connection('db_aux')->select($count_data);
                if($count_data[0]->count == 0){
                    $count_data_validated = "SELECT count(id) FROM payroll_copy_filemaker where state ='validated';";
                    $count_data_validated = DB::connection('db_aux')->select($count_data_validated);

                    if($count_data_validated[0]->count == 0){
    
                        $query = "select registration_payroll_filemakers('$connection_db_aux');";
                        $data_validated = DB::select($query);
    
                            if($data_validated){
                                $message = "Realizado con exito";
                                $successfully = true;
                                $data_payroll_copy_filemaker = "select  * from  payroll_copy_filemaker  where state ='validated';";
                                $data_payroll_copy_filemaker = DB::connection('db_aux')->select($data_payroll_copy_filemaker);
                                if(count($data_payroll_copy_filemaker)> 0){
                                    $message = "Excel";                            
                                }
                            }
                        DB::commit();
                        $data_count = $this->data_count_payroll_filemaker();
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
                            'error' => 'Error el primer paso no esta concluido o se concluyo el 3 paso.'
                        ],
                    ]);
                }
    
            }catch(Exception $e){
                DB::rollBack();
                return response()->json([
                'message' => 'Ocurrio un error.',
                'payload' => [
                    'successfully' => false,
                    'error' => $e->getMessage(),
                ],
                ]);
            }
        }
    
    // -------------metodo para verificar si existe datos en el paso 1 -----//
    public function exists_data_payroll_copy_filemaker(){
        $exists_data = true;
        $query = "SELECT * FROM payroll_copy_filemaker WHERE NOT EXISTS ( SELECT 1 FROM payroll_copy_filemaker WHERE state != 'accomplished');";
        $verify_data = DB::connection('db_aux')->select($query);

        if($verify_data == []) $exists_data = false;

        return $exists_data;
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/import_contribution_filemaker",
     *      tags={"IMPORTACION-PLANILLA-FILEMAKER"},
     *      summary="PASO 4 IMPORTACIÓN DE CONTRIBUCIONES FILEMAKER",
     *      operationId="import_contribution_filemaker",
     *      description="Importación de aportes de filemaker a la tabla contribution_passsives",
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
    public function import_contribution_filemaker(Request $request){

     try{
        DB::beginTransaction();
        $user_id = Auth::user()->id;
        $message ='No existen datos de la planilla.';

        $successfully = false;

        $count_data_payroll = "select count(id) from payroll_filemakers";
        $count_data_payroll = DB::select($count_data_payroll)[0]->count;

        $count_data_contribution = "select count(id) from contribution_passives where contributionable_type = 'payroll_filemakers'";
        $count_data_contribution = DB::select($count_data_contribution)[0]->count;

        // if($count_data_contribution > 0){
        //     $message = 'Error al realizar la importación, ya se realizo la importación de datos.';
        //     return response()->json([
        //         'message' => $message,
        //         'payload' => [
        //             'successfully' => $successfully,
        //             'num_total_data_contribution' => $count_data_contribution,
        //         ],
        //     ]);
        // }

        if($count_data_payroll > 0){
            $query ="select import_contribution_filemaker('$user_id')";
            $query = DB::select($query);
            $message ='Realizado con éxito!';
            $successfully = true;
        }
        $count_data_contribution2 = "select count(id) from contribution_passives where contributionable_type = 'payroll_filemakers'";
        $count_data_contribution2 = DB::select($count_data_contribution2)[0]->count;

        return response()->json([
            'message' => $message,
            'payload' => [
                'successfully' => $successfully,
                'num_total_data_contribution' => $count_data_contribution2,
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
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
