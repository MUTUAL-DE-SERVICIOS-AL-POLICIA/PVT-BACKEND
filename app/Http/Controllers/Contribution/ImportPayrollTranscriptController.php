<?php

namespace App\Http\Controllers\Contribution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Helpers\Util;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArchivoPrimarioExport;
use App\Models\Contribution\PayrollTranscriptPeriod;
use App\Models\Contribution\PayrollTranscript;
use App\Models\Contribution\Contribution;
use App\Models\Contribution\ContributionImportPeriod;
use Auth;

class ImportPayrollTranscriptController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/contribution/upload_copy_payroll_transcript",
     *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
     *      summary="PASO 1 COPIADO DE DATOS PLANILLA TRANSCRIPCIÓN",
     *      operationId="upload_copy_payroll_transcribed",
     *      description="Copiado de datos del archivo de planillas transcritas a la tabla payroll_copy_transcribeds",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="file", type="file", description="file required", example="file"),
     *             @OA\Property(property="image", type="file", description="file required", example="image"),
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "1999-01-01"),
     *             @OA\Property(property="number_records", type="integer",description="cantidad total de regisros required",example= "19323"),
     *             @OA\Property(property="total_amount", type="number",description="Monto total de la planilla required",example= "428865.81")
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

    public function upload_copy_payroll_transcript(request $request)
    {
        $request->validate([
            'file' => 'required',
            'image' => 'required|image|max:2048',
            'date_payroll' => 'required|date_format:"Y-m-d"',
            'number_records' => 'required|integer',
            'total_amount' => 'required|numeric',
        ]);
        $extencion = strtolower($request->file->getClientOriginalExtension());
        $file_name_entry = $request->file->getClientOriginalName();
        $image_name_entry = $request->image->getClientOriginalName();
        $extension_imge = strtolower($request->image->getClientOriginalExtension());
        $route = '';
        $route_file_name = '';
        DB::beginTransaction();
        try{
            $username = env('FTP_USERNAME');
            $password = env('FTP_PASSWORD');
            $successfully = false;
            if($extencion == "csv"){
                $date_payroll = Carbon::parse($request->date_payroll);
                $year = $date_payroll->format("Y");
                $year_format = $date_payroll->format("y");
                $month = $date_payroll->format("m");
                $month_format =(int)$month;

                $rollback_period = "delete from payroll_copy_transcripts where mes =$month_format and a_o= $year;";
                $rollback_period  = DB::connection('db_aux')->select($rollback_period);
                $file_name = "transcripcion-".$month."-".$year.'.'.$extencion;
                    if($file_name_entry == $file_name){
                        $base_path = 'planillas/planilla_transcripcion/'.$month.'-'.$year;
                        $file_path = Storage::disk('ftp')->putFileAs($base_path,$request->file,$file_name);
                        $base_path ='ftp://'.env('FTP_HOST').env('FTP_ROOT').$file_path;

                        $image_name = "foto-planilla-fisica-".$month."-".$year.'.'.$extension_imge;
                        $base_path_image = 'planillas/planilla_transcripcion/'.$month.'-'.$year;
                        $base_path_image_save = $base_path_image.'/'.$image_name;
                        $image_path = Storage::disk('ftp')->putFileAs($base_path_image,$request->image,$image_name);
                        $base_path_image ='ftp://'.env('FTP_HOST').env('FTP_ROOT').$image_path;

                        $drop = "drop table if exists payroll_copy_transcripts_tmp";
                        $drop = DB::connection('db_aux')->select($drop);

                        $temporary_payroll = "create temporary table payroll_copy_transcripts_tmp(nro integer,obs varchar,uni varchar, mes integer, a_o integer,
                        car varchar,pat varchar,mat varchar,nom varchar,nom2 varchar,
                        niv varchar,gra varchar,sue decimal(13,2),cat decimal(13,2),gan decimal(13,2),mus decimal(13,2),est decimal(13,2),
                        carg decimal(13,2),fro decimal(13,2),ori decimal(13,2))";
                        $temporary_payroll = DB::connection('db_aux')->select($temporary_payroll);

                        $copy = "copy payroll_copy_transcripts_tmp(nro,obs,uni, mes,a_o,car,pat,mat,nom,nom2,niv,gra,sue,cat,gan,mus,est,carg,fro,ori)
                                FROM PROGRAM 'wget -q -O - $@  --user=$username --password=$password $base_path'
                                WITH DELIMITER ':' CSV header;";
                        $copy = DB::connection('db_aux')->select($copy);

                        //******validación de datos****************/

                        $verify_number_records = "select count(*) from payroll_copy_transcripts_tmp";
                        $verify_number_records = DB::connection('db_aux')->select($verify_number_records);

                        if($verify_number_records[0]->count !=  $request->number_records) {
                            return response()->json([
                                'message' => 'Error en el copiado de datos',
                                'payload' => [
                                    'successfully' => false,
                                    'error' => 'El total de registros ingresado no coincide con la cantidad de registros del archivo.',
                                    'route' => $route,
                                    'route_file_name' => $route_file_name
                                ],
                            ]);
                        }

                        $verify_data = "select count(*) from payroll_copy_transcripts_tmp where mes <> $month_format or a_o <> $year or mes is null or a_o is null;";
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

                        $verify_amount = "select sum(mus) from payroll_copy_transcripts_tmp";
                        $verify_amount = DB::connection('db_aux')->select($verify_amount);

                        if($verify_amount[0]->sum !=  $request->total_amount) {
                            return response()->json([
                                'message' => 'Error en el copiado de datos',
                                'payload' => [
                                    'successfully' => false,
                                    'error' => 'El monto total ingresado no coincide con el monto total de la planilla, favor de verificar.'.$verify_amount[0]->sum . ' distinto a '.$request->total_amount,
                                    'route' => $route,
                                    'route_file_name' => $route_file_name
                                ],
                            ]);
                        }
                        //****************************************/
                        $insert = "INSERT INTO payroll_copy_transcripts(obs,uni,mes,a_o,car,pat,mat,nom,nom2,niv,gra,sue,cat,gan,mus,est,carg,fro,ori,created_at,updated_at)
                                   SELECT obs,uni,mes::INTEGER,a_o::INTEGER,car,pat,mat,nom,nom2,niv,gra,sue,cat,gan,mus,est,carg,fro,ori,current_timestamp,current_timestamp FROM payroll_copy_transcripts_tmp; ";
                        $insert = DB::connection('db_aux')->select($insert);

                        $drop = "drop table if exists payroll_copy_transcripts_tmp";
                        $drop = DB::connection('db_aux')->select($drop);

                        $data_count = $this->data_count_payroll_transcript($month_format,$year);

                        //******validación de datos****************/
                        $verify_data = "update payroll_copy_transcripts pt set error_messaje = concat(error_messaje,' - ','Los valores de los apellidos son NULOS ') from (select id from payroll_copy_transcripts where mes =$month_format and a_o= $year and pat is null and mat is null) as subquery where pt.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "update payroll_copy_transcripts pt set error_messaje = concat(error_messaje,' - ','El valor del primer nombre es NULO ') from (select id from payroll_copy_transcripts where mes =$month_format and a_o= $year and nom is null) as subquery where pt.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "update payroll_copy_transcripts pt set error_messaje = concat(error_messaje,' - ','El monto del aporte es 0 o inferior ') from (select id from payroll_copy_transcripts where mes =$month_format and a_o= $year and mus <= 0) as subquery where pt.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "update payroll_copy_transcripts pt set error_messaje = concat(error_messaje,' - ','El numero de carnet es duplicado ') from (select car,count(car) from payroll_copy_transcripts where mes =$month_format and a_o= $year group by car having count(car) > 1) as subquery where pt.car = subquery.car;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "select count(id) from payroll_copy_transcripts pct where mes =$month_format and a_o= $year and error_messaje is not null;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        if($verify_data[0]->count > 0) {
                            $route = '/contribution/download_error_data_archive';
                            $route_file_name = 'datos_observados_archivo.xls';
                            return response()->json([
                                'message' => 'Excel',
                                'payload' => [
                                    'successfully' => false,
                                    'error' => 'Existen datos en el archivo que son incorrectos, favor revisar.',
                                    'route' => $route,
                                    'route_file_name' => $route_file_name
                                ],
                            ]);
                        }
                        //****************************************/
                        DB::commit();

                        $payroll_period = new PayrollTranscriptPeriod;
                        $payroll_period->updateOrInsert(
                            ['month_p' => $month_format, 'year_p' => $year],
                            ['total_amount' => $request->total_amount,'number_records' => $request->number_records,'img'=> $base_path_image_save]
                        );

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

    public function data_count_payroll_transcript($month,$year){
        $data_count['num_total_data_copy'] = 0;

        //---TOTAL DE DATOS DEL ARCHIVO
        $query_total_data = "SELECT count(id) FROM payroll_copy_transcripts where mes = $month::INTEGER and a_o = $year::INTEGER;";
        $query_total_data = DB::connection('db_aux')->select($query_total_data);
        $data_count['num_total_data_copy'] = $query_total_data[0]->count;

        return  $data_count;
    }
     /**
      * @OA\Post(
      *      path="/api/contribution/download_error_data_archive",
      *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
      *      summary="DESCARGA EL ARCHIVO, CON EL LISTADO DE AFILIADOS QUE TENGAN OBSERVACIONES EN EL ARCHIVO",
      *      operationId="download_error_data_archive",
      *      description="Descarga el archivo con el listado de afiliados con CI duplicado, primer nombre nulo, apellido paterno y materno en nulo ",
      *      @OA\RequestBody(
      *          description= "Provide auth credentials",
      *          required=true,
      *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
      *             @OA\Property(property="date_payroll", type="string", description="fecha de planilla required", example= "2021-10-01")
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
    public function download_error_data_archive(Request $request){
        $request->validate([
            'date_payroll' => 'required|date_format:"Y-m-d"',
        ]);
    $message = "No hay datos";
    $data_header=array(array("AÑO","MES","CARNET","APELLIDO PATERNO","APELLIDO MATERNO","PRIMER NOMBRE","SEGUNDO NOMBRE","APORTE","OBSERVACIÓN"));
    $date_payroll = Carbon::parse($request->date_payroll);
    $year = (int)$date_payroll->format("Y");
    $month = (int)$date_payroll->format("m");
    $data_payroll_copy_transcripts = "select a_o,mes,car,pat,mat,nom,nom2,mus,error_messaje from payroll_copy_transcripts pct where mes ='$month' and a_o ='$year' and error_messaje is not null or error_messaje ='' order by car";
    $data_payroll_copy_transcripts = DB::connection('db_aux')->select($data_payroll_copy_transcripts);
        foreach ($data_payroll_copy_transcripts as $row){
            array_push($data_header, array($row->a_o,$row->mes,$row->car,$row->pat,
            $row->mat,$row->nom,$row->nom2,$row->mus,$row->error_messaje));
        }
        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "observacion-planilla-transcrita";
        $extension = '.xls';
        return Excel::download($export, $file_name."_".$month."_".$year.$extension);
    }

     /**
     * @OA\Post(
     *      path="/api/contribution/validation_affiliate_transcript",
     *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
     *      summary="PASO 2 VALIDACION AFILIADOS TRANSCRITOS",
     *      operationId="validation_affiliate_transcript",
     *      description="validacion de Afiliados de la planilla transcrita",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "1999-01-01")
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

    public function validation_affiliate_transcript(Request $request){
        $request->validate([
        'date_payroll' => 'required|date_format:"Y-m-d"',
        ]);
            try{
                DB::beginTransaction();
                $message = "No hay datos por validar";
                $successfully =false;
                $data_count['num_total_data_copy'] = 0;
                $data_count['count_data_automatic_link'] = 0;
                $data_count['count_data_revision'] = 0;
                $data_count['count_data_creation'] = 0;
                $data_count['num_total_data_payroll'] = 0;
                $data_count['num_total_data_contribution'] = 0;
                $route = '';
                $route_file_name = '';
                $date_payroll_format = $request->date_payroll;
                $date_payroll = Carbon::parse($request->date_payroll);
                $year = (int)$date_payroll->format("Y");
                $month = (int)$date_payroll->format("m");

                $connection_db_aux = Util::connection_db_aux();

                $query = "select search_affiliate_transcript('$connection_db_aux',$month,$year);";
                $data_validated = DB::select($query);

                $num_total_data_copy = $this->data_count_payroll_transcript($month,$year);

                $count_data_automatic_link = "select count(id) from payroll_copy_transcripts pct where mes ='$month' and a_o ='$year' and criteria in ('1-CI-PN-PA-SA','2-CI-sPN-sPA-sSA','3-partCI-PN-PA-SA')";
                $count_data_automatic_link = DB::connection('db_aux')->select($count_data_automatic_link);

                $count_data_revision = "select count(id) from payroll_copy_transcripts pct where mes ='$month' and a_o ='$year' and criteria in ('4-CI','5-sCI-sPN-sAP-sSN-FI')";
                $count_data_revision = DB::connection('db_aux')->select($count_data_revision);

                $count_data_creation = "select count(id) from payroll_copy_transcripts pct where mes ='$month' and a_o ='$year' and criteria in ('6-CREAR')";
                $count_data_creation = DB::connection('db_aux')->select($count_data_creation);

                $data_count['num_total_data_copy'] = $num_total_data_copy['num_total_data_copy'];
                $data_count['count_data_automatic_link'] = $count_data_automatic_link[0]->count;
                $data_count['count_data_revision'] = $count_data_revision[0]->count;
                $data_count['count_data_creation'] = $count_data_creation[0]->count;

                $validated_contriburion = $this->validation_contribution_transcript($date_payroll_format);

                if($num_total_data_copy['num_total_data_copy'] <= 0){
                    $successfully =false;
                    $message = 'no existen datos';
                }elseif($count_data_revision[0]->count > 0){
                    $successfully =false;
                    $message = 'Excel';
                    $route = '/contribution/download_data_revision';
                    $route_file_name = 'observados_para_revision.xls';
                }elseif($count_data_revision[0]->count == 0 && $count_data_creation[0]->count > 0){

                    $valid_contribution =  "select count(id) from payroll_copy_transcripts pct  where state like 'accomplished' and error_messaje is not null and mes ='$month' and a_o ='$year'";
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
                }elseif($count_data_revision[0]->count == 0 && $count_data_creation[0]->count == 0){
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
    /**
      * @OA\Post(
      *      path="/api/contribution/download_data_revision",
      *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
      *      summary="DESCARGA EL ARCHIVO, PARA LA REVISIÓN DE DATOS DE LOS AFILIADOS",
      *      operationId="download_data_revision",
      *      description="Descarga el archivo, para la revisión de datos de los afiliados identificados con CI iguales y CI Distintos",
      *      @OA\RequestBody(
      *          description= "Provide auth credentials",
      *          required=true,
      *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
      *             @OA\Property(property="date_payroll", type="string", description="fecha de planilla required", example= "1999-01-01")
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
    public function download_data_revision(Request $request){
        $request->validate([
            'date_payroll' => 'required|date_format:"Y-m-d"',
        ]);
    $message = "No hay datos";
    $data_header=array(array("AÑO","MES","CARNET","APELLIDO PATERNO","APELLIDO MATERNO","PRIMER NOMBRE","SEGUNDO NOMBRE","APORTE","DETALLE PARA REVISIÓN","***","NUP-AFILIADO CON SIMILITUD"));
    $date_payroll = Carbon::parse($request->date_payroll);
    $year = (int)$date_payroll->format("Y");
    $month = (int)$date_payroll->format("m");
    $data_payroll_copy_transcripts = "select a_o,mes,car,pat,mat,nom,nom2,mus,'***',
    (CASE WHEN (criteria = '4-CI' OR criteria = '5-sCI-sPN-sAP-sSN-FI' ) then
         'IDENTIFICADO PARA SUBSANAR'
     ELSE
         'NO SE ENCONTRÓ SIMILITUDES, FAVOR DE REVISAR ANTES DE SU CREACIÓN'
    END) as criteria, affiliate_id from payroll_copy_transcripts pct where mes ='$month' and a_o ='$year' and criteria in('4-CI','5-sCI-sPN-sAP-sSN-FI','6-CREAR') order by criteria DESC";
    $data_payroll_copy_transcripts = DB::connection('db_aux')->select($data_payroll_copy_transcripts);
        foreach ($data_payroll_copy_transcripts as $row){
            array_push($data_header, array($row->a_o,$row->mes,$row->car,$row->pat,
            $row->mat,$row->nom,$row->nom2,$row->mus,$row->criteria,'***',$row->affiliate_id));
        }
        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "observacion-data-revision";
        $extension = '.xls';
        return Excel::download($export, $file_name."_".$month."_".$year.$extension);
    }
    /**
     * @OA\Post(
     *      path="/api/contribution/list_months_import_contribution_transcript",
     *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
     *      summary="LISTA LOS MESES QUE SE REALIZARON IMPORTACIONES A LA TABLA CONTRIBUTIONS TRANSCRITOS DE COMANDO EN BASE A UN AÑO DADO EJ:1999",
     *      operationId="list_months_import_contribution_transcript",
     *      description="Lista los meses importados de las contribuciones de comando, en la tabla contributions enviando como parámetro un año en específico",
     *     @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *             @OA\Property(property="period_year", type="integer",description="Año de contribución a listar",example= "1999"),
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

    public function list_months_import_contribution_transcript(Request $request)
    {
        $request->validate([
            'period_year' => 'required|date_format:"Y"',
            'with_data_count'=>'boolean'
        ]);
        $with_data_count = !isset($request->with_data_count) || is_null($request->with_data_count)? true:$request->with_data_count;
        $period_year = $request->get('period_year');
        $contributionable_type = 'payroll_transcripts';
        $query = "SELECT distinct month_year, to_char( month_year, 'TMMonth') as period_month_name, extract(year from month_year) as period_year,extract(month from month_year) as period_month  from contributions where deleted_at is null and (extract(year from month_year::timestamp)) = $period_year and contributionable_type = 'payroll_transcripts' group by month_year;";
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
           if($with_data_count)
           $month->data_count = $this->data_count($month->period_month,$period_year);
        }
        return response()->json([
            'message' => "Exito",
            'payload' => [
                'list_months' =>  $query_months,
                'count_months' =>  count($query)
            ],
        ]);
    }
    /**
     * @OA\Post(
     *      path="/api/contribution/rollback_payroll_copy_transcripts",
     *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
     *      summary="REHACER IMPORTACIÓN PLANILLA TRANSCRIPCIÓN",
     *      operationId="rollback_payroll_copy_transcript",
     *      description="Para rehacer la importación de planilla transcripción",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "1999-01-01")
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

    public function rollback_payroll_copy_transcripts(Request $request)
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

           if($this->exists_data_payroll_copy_transcrips($month,$year) && !PayrollTranscript::data_period($month,$year)['exist_data']){
               $result['delete_step_1'] = $this->delete_payroll_copy_transcrips($month,$year);

               if($result['delete_step_1'] == true){
                   $valid_rollback = true;
                   $message = "Realizado con éxito!";
               }
           }else{
               if(PayrollTranscript::data_period($month,$year)['exist_data'])
               $message = "No se puede rehacer, por que ya realizó la validación del la planilla de transcrita";
               else
                   $message = "No existen datos para rehacer";
           }

           DB::commit();

           return response()->json([
               'message' => $message,
               'payload' => [
                   'valid_rollback' =>  $valid_rollback,
                   'delete_step' =>  $result
               ],
           ]);
       }catch (Exception $e)
       {
           DB::rollback();
           return $e;
       }
    }
     //borrado de datos de la tabla payroll_copy_commands paso 1
     public function delete_payroll_copy_transcrips($month, $year)
     {
              if($this->exists_data_payroll_copy_transcrips($month,$year))
              {
                 $query = "delete from payroll_copy_transcripts where a_o = $year::INTEGER and mes = $month::INTEGER ";
                 $query = DB::connection('db_aux')->select($query);
                 DB::commit();
                 return true;
              }
              else
                  return false;
     }

      //método para verificar si existe datos en el paso 1 

    public function exists_data_payroll_copy_transcrips($month,$year){
        $exists_data = true;
        $query = "select * from payroll_copy_transcripts where mes = $month::INTEGER and a_o = $year::INTEGER;";
        $verify_data = DB::connection('db_aux')->select($query);

        if($verify_data == []) $exists_data = false;

        return $exists_data;
    }
     //método para verificar si existe montos con diferentes contribuciones
     public function validation_contribution_transcript($date_payroll){
        $different_contribution = false;
        $date_payroll = Carbon::parse($date_payroll);
        $year = (int)$date_payroll->format("Y");
        $month = (int)$date_payroll->format("m");
        $connection_db_aux = Util::connection_db_aux();
        $payroll_transcripts =  DB::select("SELECT pct.id, c.affiliate_id, pct.mus, c.total
        FROM contributions c
        JOIN dblink('$connection_db_aux', 'SELECT id, affiliate_id, a_o, mes, mus FROM payroll_copy_transcripts')
        AS pct(id INT, affiliate_id INT, a_o INT, mes INT, mus NUMERIC(13,2)) ON c.affiliate_id = pct.affiliate_id
        where c.month_year = '$date_payroll' AND c.total > 0 AND pct.a_o = $year AND pct.mes = $month AND c.total <> pct.mus");
        foreach($payroll_transcripts as  $update_payroll){
            $verify_data = "update payroll_copy_transcripts pt set error_messaje = 'La contribución registrada anteriormente es: $update_payroll->total difiere al de la planilla $update_payroll->mus' where pt.id = $update_payroll->id;";
            $verify_data = DB::connection('db_aux')->select($verify_data);
            $different_contribution = true;
        }
        if($different_contribution == true){
            return false;
        }else{
            return true;
        }
    }
     /**
     * @OA\Post(
     *      path="/api/contribution/import_payroll_transcript_progress_bar",
     *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
     *      summary="INFORMACIÓN DE PROGRESO DE IMPORTACIÓN PLANILLA TRANSCRIPCIÓN",
     *      operationId="import_payroll_transcript_progress_bar",
     *      description="Muestra la información de la importación de transcripciones  (-1)Si existió algún error en algún paso, (100) Si todo fue exitoso, (25 50 75)paso 1,2,3 respectivamente (0)si esta iniciando la importación",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "1999-01-01")
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

    public function import_payroll_transcript_progress_bar(Request $request){
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
        $result['query_step_3'] = false;
        $result['query_step_4'] = false;

        $task['task_step_1'] = false;
        $task['task_step_2'] = false;
        $task['task_step_3'] = false;
        $task['task_step_4'] = false;

        $task['task_step_1'] = $this->exists_data_payroll_copy_transcrips($month,$year);
        //****** paso 2 *****/
        $step_2 = "select count(id) from payroll_copy_transcripts where mes = $month::INTEGER and a_o = $year::INTEGER and (error_messaje is not null or criteria like '4-CI')";
        $step_2 = DB::connection('db_aux')->select($step_2);

        $step = "select count(id) from payroll_copy_transcripts where mes = $month::INTEGER and a_o = $year::INTEGER and state like 'accomplished'";
        $step = DB::connection('db_aux')->select($step);

        $task['task_step_2']  = $this->exists_data_payroll_copy_transcrips($month,$year) && $step_2[0]->count == 0 && $step[0]->count > 0? true : false;
        //****** paso 3 *****/
        $step_3 = "select count(id) from payroll_transcripts where month_p = $month::INTEGER and year_p = $year::INTEGER";
        $step_3 = DB::select($step_3);
        $task['task_step_3'] = $step_3[0]->count > 0? true : false;
        //****** paso 3 *****/
        $step_4 = "select count(id) from contributions where month_year = '$request->date_payroll' and contributionable_type like 'payroll_transcripts';";
        $step_4 = DB::select($step_4);
        $task['task_step_4'] = $step_4[0]->count > 0? true : false;

        //verificamos si existe el archivo de importación
        $date_month= strlen($month)==1?'0'.$month:$month;
        $new_file_name = "transcripcion-".$date_month."-".$year.'.csv';
        $base_path = 'planillas/planilla_transcripcion/'.$date_month.'-'.$year.'/'.$new_file_name;
        if (Storage::disk('ftp')->has($base_path)) {
            $result['file_name'] = $new_file_name;
            $result['file_exists'] = true;
        }

        if($result['file_exists'] == true && $task['task_step_1'] == true && $task['task_step_2']  == true && $task['task_step_3'] == true && $task['task_step_4'] == true){
            $result['percentage'] = 100;
        }elseif($result['file_exists'] == true && $task['task_step_1'] == true && $task['task_step_2']  == false && $task['task_step_3'] == false && $task['task_step_4'] == false){
            $result['percentage'] = 25;
            $result['query_step_2'] = true;
        }elseif($result['file_exists'] == true && $task['task_step_1'] == true && $task['task_step_2']  == true && $task['task_step_3'] == false && $task['task_step_4'] == false){
            $result['percentage'] = 50;
            $result['query_step_3'] = true;
        }elseif($result['file_exists'] == true && $task['task_step_1'] == true && $task['task_step_2']  == true && $task['task_step_3'] == true && $task['task_step_4'] == false){
            $result['percentage'] = 75;
            $result['query_step_4'] = true;
        }elseif($task['task_step_1'] == false && $task['task_step_2']  == false && $task['task_step_3'] == false && $task['task_step_4'] == false){
            $result['percentage'] = 0;
        }else{
            $result['percentage'] = -1;
            $message = "Error! Algo salió mal en algún paso.";
        }

        return response()->json([
            'message' => $message,
            'payload' => [
                'import_progress_bar' =>  $result,
                'data_count' =>  $this->data_count($month,$year),
                'task'=>$task
            ],
        ]);
    }

    public function data_count($month,$year){
        $data_count['num_total_data_copy'] = 0;
        $data_count['count_data_automatic_link'] = 0;
        $data_count['count_data_revision'] = 0;
        $data_count['count_data_creation'] = 0;
        $data_count['num_total_data_payroll'] = 0;
        $data_count['num_total_data_contribution'] = 0;

        $date_payroll = Carbon::create($year, $month, 1)->format('Y-m-d');

        //---TOTAL DE DATOS DEL ARCHIVO
        $query_total_data = "SELECT count(id) FROM payroll_copy_transcripts where mes = $month::INTEGER and a_o = $year::INTEGER;";
        $query_total_data = DB::connection('db_aux')->select($query_total_data);
        $data_count['num_total_data_copy'] = $query_total_data[0]->count;

        $count_data_automatic_link = "select count(id) from payroll_copy_transcripts pct where mes ='$month' and a_o ='$year' and criteria in ('1-CI-PN-PA-SA','2-CI-sPN-sPA-sSA','3-partCI-PN-PA-SA')";
        $count_data_automatic_link = DB::connection('db_aux')->select($count_data_automatic_link);

        $count_data_revision = "select count(id) from payroll_copy_transcripts pct where mes ='$month' and a_o ='$year' and criteria in ('4-CI','5-sCI-sPN-sAP-sSN-FI')";
        $count_data_revision = DB::connection('db_aux')->select($count_data_revision);

        $count_data_creation = "select count(id) from payroll_copy_transcripts pct where mes ='$month' and a_o ='$year' and criteria in ('6-CREAR')";
        $count_data_creation = DB::connection('db_aux')->select($count_data_creation);

        $data_count['count_data_automatic_link'] = $count_data_automatic_link[0]->count;
        $data_count['count_data_revision'] = $count_data_revision[0]->count;
        $data_count['count_data_creation'] = $count_data_creation[0]->count;

        //conteo planilla
        $num_total_data_payroll = "select count(id) from payroll_transcripts pt where month_p ='$month' and year_p ='$year'";
        $num_total_data_payroll = DB::select($num_total_data_payroll);

        //conteo aportes
        $num_total_data_contribution = "select count(id) from contributions c where month_year = '$date_payroll' and c.contributionable_type like 'payroll_transcripts'";
        $num_total_data_contribution = DB::select($num_total_data_contribution);

        $data_count['num_total_data_payroll'] = $num_total_data_payroll[0]->count;
        $data_count['num_total_data_contribution'] = $num_total_data_contribution[0]->count;

        return  $data_count;
    }
    /**
     * @OA\Post(
     *      path="/api/contribution/import_payroll_transcript",
     *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
     *      summary="PASO 3 IMPORTACIÓN DE PLANILLA TRANSCRIPCIÓN",
     *      operationId="import_payroll_transcript",
     *      description="Paso 3 importación de planilla transcripción",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "1999-01-01")
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
    public function import_payroll_transcript(Request $request){
        $request->validate([
            'date_payroll' => 'required|date_format:"Y-m-d"',
          ]);
        $message = "Realizado con éxito";
        $successfully = false;
        $date_payroll = Carbon::parse($request->date_payroll);
        $year = (int)$date_payroll->format("Y");
        $month = (int)$date_payroll->format("m");
        $connection_db_aux = Util::connection_db_aux();
        $user = Auth::user();
        DB::beginTransaction();
        try{
        //conteo de  affiliate_id is null distito del criterio 6-CREAR
        $count_data_validated_affiliate = "SELECT count(id) FROM payroll_copy_transcripts where mes = $month::INTEGER and a_o = $year::INTEGER and affiliate_id is null and criteria!='6-CREAR';";
        $count_data_validated_affiliate = DB::connection('db_aux')->select($count_data_validated_affiliate);
        if($count_data_validated_affiliate[0]->count == 0){
            //conteo de criterio similares
            $count_data_similarity = "SELECT count(id) FROM payroll_copy_transcripts where mes = $month::INTEGER and a_o = $year::INTEGER and criteria in ('4-CI','5-sCI-sPN-sAP-sSN-FI');";
            $count_data_similarity = DB::connection('db_aux')->select($count_data_similarity);
            if($count_data_similarity[0]->count == 0){
               //conteo de registros payroll_transcripts
                $exists_data_payroll = "select count(id) from payroll_transcripts where month_p = $month::INTEGER and year_p = $year::INTEGER";
                $exists_data_payroll = DB::select($exists_data_payroll);
                if($exists_data_payroll[0]->count == 0){
                    $query_create_affiliate = "select registration_payroll_command_transcript('$connection_db_aux',$user->id,$month,$year);";
                    $data_validated = DB::select($query_create_affiliate);
                    DB::commit();
                    $successfully = true;
                }else{
                    if($exists_data_payroll[0]->count > 0){
                       $successfully = true;
                    }
                }
                $contribution_import_period = new ContributionImportPeriod;
                $contribution_import_period->updateOrInsert(
                    ['month_year' => $request->date_payroll,'table' => 'payroll_transcripts'],
                    ['type' => 'planilla-activo']
                );
                return response()->json([
                    'message' => $message,
                    'payload' => [
                       'successfully' => $successfully,
                       'data_count' => $this->data_count($month,$year)
                    ],
                ]);
            }else{
                return response()->json([
                    'message' => 'Error en el copiado de datos',
                    'payload' => [
                       'successfully' => $successfully,
                       'data_count' => $this->data_count($month,$year)
                    ],
                ]);
            }
        }else{
            return response()->json([
                'message' => 'Error en el copiado de datos',
                'payload' => [
                   'successfully' => $successfully,
                   'data_count' => $this->data_count($month,$year)
                ],
            ]);
        }
    }catch(Exception $e){
    DB::rollBack();
    return response()->json([
    'message' => 'Error al realizar la importación',
    'payload' => [
        'successfully' => $successfully,
        'error' => $e->getMessage(),
        ],
    ]);
    }
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/import_contribution_transcript",
     *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
     *      summary="PASO 4 IMPORTACIÓN DE TRANSCRIPCIONES",
     *      operationId="import_contribution_transcript",
     *      description="Importación de aportes de Transcripciones",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="date_payroll", type="string",description="fecha de aporte required",example= "1999-01-01")
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
    public function import_contribution_transcript(Request $request){
        $request->validate([
            'date_payroll' => 'required|date_format:"Y-m-d"',
        ]);
     try{
        DB::beginTransaction();
        $user_id = Auth::user()->id;
        $message ='No existen datos de la planilla.';
        $count_created = 0;
        $successfully = false;
        $date_payroll = Carbon::parse($request->date_payroll);
        $year = (int)$date_payroll->format("Y");
        $month = (int)$date_payroll->format("m");
        $date_payroll = $request->date_payroll;

        $count_data_payroll = "select count(id) from payroll_transcripts where month_p = $month::INTEGER and year_p = $year::INTEGER";
        $count_data_payroll = DB::select($count_data_payroll)[0]->count;

        $count_data_contribution = "select count(id) from contributions where month_year = '$date_payroll' and contributionable_type = 'payroll_transcripts'";
        $count_data_contribution = DB::select($count_data_contribution)[0]->count;

        if(!Contribution::exist_contribution_rate($date_payroll)){
            $message ="No existe la taza de contribución para el periodo : ". $date_payroll.", el dato es requerido para continuar.";
            return response()->json([
                'message' => $message,
                'payload' => [
                    'successfully' => $successfully,
                    'num_total_data_contribution' => $count_data_contribution,
                ],
            ]);
        }

        if($count_data_contribution > 0){
            $message = 'Error al realizar la importación, el periodo ya fue importado.'.$date_payroll;
            return response()->json([
                'message' => $message,
                'payload' => [
                    'successfully' => $successfully,
                    'num_total_data_contribution' => $count_data_contribution,
                ],
            ]);
        }

        if($count_data_payroll > 0){
            $query ="select import_period_contribution_transcript('$request->date_payroll',$user_id,$year,$month)";
            $query = DB::select($query);
            $message ='Realizado con éxito!';
            $successfully = true;
            $contribution_import_period = new ContributionImportPeriod;
            $contribution_import_period->updateOrInsert(
                ['month_year' => $date_payroll,'table' => 'contributions','type' => 'aporte-activo']
            );
            DB::commit();
        }
        $count_data_contribution = "select count(id) from contributions where month_year = '$date_payroll' and contributionable_type = 'payroll_transcripts'";
        $count_data_contribution = DB::select($count_data_contribution)[0]->count;

        return response()->json([
            'message' => $message,
            'payload' => [
                'successfully' => $successfully,
                'num_total_data_contribution' => $count_data_contribution,
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
     *      path="/api/contribution/report_import_contribution_transcript",
     *      tags={"IMPORTACION-PLANILLA-TRANSCRIPCIÓN"},
     *      summary="GENERA REPORTE DE APORTES  TRANSCRITAS IMPORTADAS",
     *      operationId="report_import_contribution_transcript",
     *      description="Genera reporte de aportes TRANSCRITAS de la tabla contribution de acuerdo a periodo de planilla",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "1999-01-01")
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
    public function report_import_contribution_transcript(request $request) {

        $request->validate([
            'date_payroll' => 'required|date_format:"Y-m-d"',
        ]);
        $message = "No hay datos";
       // ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        $date_contribution_format = $request->date_payroll;

        $data_cabeceras=array(array("PERIODO","TIPO","NUP","CÉDULA_DE_IDENTIDAD","UNIDAD","DESGLOSE","PATERNO",
        "MATERNO","AP_CASADA", "P_NOMBRE","S_NOMBRE","ESTADO_CIVIL","GRADO", "CATEGORÍA","SUELDO_BASE","BONO_ANTIGÜEDAD", "BONO_ESTUDIO",
        "BONO_A_CARGO","BONO_FRONTERA","BONO_ORIENTE","TOTAL_GANADO","LÍQUIDO_PAGABLE","COTIZABLE","FONDO_DE_RETIRO",
        "CUOTA_MORTUORIA","TOTAL_APORTE"));

        $date_payroll = Carbon::parse($request->date_payroll);
        $year = (string)$date_payroll->format("Y");
        $month = (string)$date_payroll->format("m");
        $day = (string)$date_payroll->format("d");
        $date_payroll = $year.'-'.$month.'-'.$day;
        $data_contribution_transcript = "select c.month_year, c.type, a.id, a.identity_card, u.name, b.name as breakdown, a.last_name, a.mothers_last_name, 
        a.surname_husband, a.first_name, a.second_name, a.civil_status, d.name as degree, c3.name as category, c.base_wage, c.seniority_bonus,c.study_bonus, 
        c.position_bonus, c.border_bonus, c.east_bonus, c.gain, c.payable_liquid, c.quotable, c.retirement_fund, c.mortuary_quota, c.total
        from contributions c 
        left join affiliates a on c.affiliate_id = a.id
        left join units u on u.id = c.unit_id
        left join breakdowns b on b.id = c.breakdown_id
        left join degrees d on d.id = c.degree_id
        left join categories c3 on c3.id = c.category_id
        where c.type = 'Planilla' and c.month_year = '$date_contribution_format'
        and contributionable_type = 'payroll_transcripts'";
                    $data_contribution_transcript = DB::select($data_contribution_transcript);

                            if(count($data_contribution_transcript)> 0){
                                $message = "Excel";
                                foreach ($data_contribution_transcript as $row){
                                    array_push($data_cabeceras, array($row->month_year ,'PLANILLA TRANSCRITA' ,$row->id ,$row->identity_card,
                                    $row->name, $row->breakdown, $row->last_name , $row->mothers_last_name ,$row->surname_husband, $row->first_name,
                                    $row->second_name,$row->civil_status,$row->degree,$row->category,$row->base_wage,$row->seniority_bonus,$row->study_bonus,$row->position_bonus,
                                    $row->border_bonus, $row->east_bonus,$row->gain, $row->payable_liquid,$row->quotable,
                                    $row->retirement_fund,$row->mortuary_quota,$row->total));
                                }

                                $export = new ArchivoPrimarioExport($data_cabeceras);
                                $file_name = "Aportes_transcripcion";
                                $extension = '.xls';
                                return Excel::download($export, $file_name."_".$month.'-'.$year.$extension);

                            }else{
                                return response()->json([
                                    'message' => "Error! no existen datos de importación del periodo ".$date_contribution_format,
                                    ],
                                );
                            }
    }
}
