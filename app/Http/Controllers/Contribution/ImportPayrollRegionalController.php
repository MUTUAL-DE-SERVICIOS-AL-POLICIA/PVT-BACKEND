<?php

namespace App\Http\Controllers\Contribution;

use App\Exports\ArchivoPrimarioExport;
use App\Helpers\Util;
use App\Http\Controllers\Controller;
use App\Models\Contribution\PayrollRegional;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

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
     *              @OA\Property(property="file", type="file", description="file required", example="file"),
     *              @OA\Property(property="date_import", type="string", description="fecha importacion required", example= "2025-11-07")
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
        $request->validate([
            'file' => 'required',
            'date_import' => 'required|date'
        ]);
        
        $date_import = Carbon::parse($request->date_import)->format('Y-m-d');
        $extension = strtolower($request->file->getClientOriginalExtension());
        $file_name_entry = $request->file->getClientOriginalName();

        $uploadedFile = $request->file('file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $file_name_entry = $uploadedFile->getClientOriginalName();

        $route = '';
        $route_file_name = '';
        DB::beginTransaction();
        try{
            $username = env('FTP_USERNAME');
            $password = env('FTP_PASSWORD');
            $successfully = false;
            if($extension == "csv"){
                $rollback_period = "DELETE FROM payroll_copy_regionals WHERE state = 'unrealized' AND created_at::date ='".$date_import."';";
                $rollback_period = DB::connection('db_aux')->select($rollback_period);
                $file_name = "regional".'.'.$extension;
                    if($file_name_entry == $file_name){
                        $base_path = 'planillas/planilla_regional/'.Carbon::now()->toDateString();
                        $file_path = Storage::disk('ftp')->putFileAs($base_path, $request->file, $file_name);
                        $base_path ='ftp://'.env('FTP_HOST').env('FTP_ROOT').$file_path;

                        $drop = "DROP TABLE IF EXISTS payroll_copy_regional_tmp";
                        $drop = DB::connection('db_aux')->select($drop);

                        $temporary_payroll = "CREATE TEMPORARY TABLE payroll_copy_regional_tmp(
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
                                                porcentaje_aporte decimal(13,2))";

                        $temporary_payroll = DB::connection('db_aux')->select($temporary_payroll);
            
                        $copy = "COPY payroll_copy_regional_tmp(carnet, tipo_aportante, nom, nom2, pat, mat, ap_casada, recibo, fecha_deposito, total_depositado, mes, a_o, total_pension, renta_dignidad, cotizable, aporte, porcentaje_aporte)
                                FROM PROGRAM 'wget -q -O - $@  --user=$username --password=$password $base_path'
                                WITH DELIMITER ':' CSV header;";
                        $copy = DB::connection('db_aux')->select($copy);

                        // Validación de datos
                        $verify_number_records = "SELECT COUNT(*) FROM payroll_copy_regional_tmp";
                        $verify_number_records = DB::connection('db_aux')->select($verify_number_records);

                        $verify_data = "SELECT COUNT(*) FROM payroll_copy_regional_tmp where a_o is null or mes is null;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        if($verify_data[0]->count > 0){
                            return response()->json([
                                'message' => 'Error en el copiado de datos',
                                'payload' => [
                                    'successfully' => false,
                                    'error' => 'Existen datos incorrectos en las columnas de mes o año.',
                                    'route' => $route,
                                    'route_file_name' => $route_file_name
                                ],
                            ]);
                        }

                        $insert = "INSERT INTO payroll_copy_regionals(carnet, tipo_aportante, nom, nom2, pat, mat, ap_casada, recibo, fecha_deposito, total_depositado, mes, a_o, total_pension, renta_dignidad, cotizable, aporte, porcentaje_aporte, created_at, updated_at)
                        SELECT carnet, tipo_aportante, nom, nom2, pat, mat, ap_casada, recibo, fecha_deposito::DATE, total_depositado::DECIMAL(13,2), mes::INTEGER, a_o::INTEGER, total_pension::DECIMAL(13,2), renta_dignidad::DECIMAL(13,2),
                        cotizable::DECIMAL(13,2), aporte::DECIMAL(13,2), porcentaje_aporte::DECIMAL(13,2), current_timestamp , current_timestamp
                        FROM payroll_copy_regional_tmp";
                        $insert = DB::connection('db_aux')->select($insert);
                        
                        $drop = "DROP TABLE IF EXISTS payroll_copy_regional_tmp";
                        $drop = DB::connection('db_aux')->select($drop);

                        $data_count = $this->data_count_payroll_regional($date_import);                     

                        $data_cleaning = "WITH duplicados AS (
                            SELECT id,
                                    ROW_NUMBER() OVER (
                                        PARTITION BY carnet, tipo_aportante, a_o, mes, aporte, cotizable, total_pension, renta_dignidad
                                        ORDER BY id
                                    ) AS rn
                                FROM payroll_copy_regionals
                                WHERE created_at::date = '".$date_import."'
                            )
                            UPDATE payroll_copy_regionals
                            SET deleted_at = NOW(),
                            error_message = COALESCE(error_message, '') || 'Registro duplicado en la planilla'
                            WHERE created_at::date = '".$date_import."' AND
                            id IN (
                                SELECT id FROM duplicados WHERE rn > 1
                            );";
                        $data_cleaning = DB::connection('db_aux')->select($data_cleaning);

                        $verify_data = "UPDATE payroll_copy_regionals pcr SET error_message = 
                                CASE 
                                    WHEN error_message IS NULL OR error_message = '' THEN
                                        'Los valores de los apellidos son NULOS'
                                    ELSE
                                        error_message || ' - ' || 'Los valores de los apellidos son NULOS'
                                    END    
                            FROM (
                                SELECT id FROM payroll_copy_regionals WHERE a_o IS NULL AND mes IS NULL AND pat IS NULL AND mat IS NULL AND deleted_at IS NULL AND created_at::date = '".$date_import."') AS subquery where pcr.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "UPDATE payroll_copy_regionals pcr SET error_message = 
                                CASE 
                                    WHEN error_message IS NULL OR error_message = '' THEN
                                        'El valor del primer nombre es NULO'
                                    ELSE
                                        error_message || ' - ' || 'El valor del primer nombre es NULO'
                                    END    
                            FROM (
                                SELECT id FROM payroll_copy_regionals WHERE nom IS NULL AND deleted_at IS NULL AND created_at::date = '".$date_import."') as subquery where pcr.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "UPDATE payroll_copy_regionals pcr SET error_message = 
                                CASE 
                                    WHEN error_message IS NULL OR error_message = '' THEN
                                        'El número de carnet es duplicado en el mismo periodo'
                                    ELSE
                                        error_message || ' - ' || 'El número de carnet es duplicado en el mismo periodo'
                                    END    
                            FROM (
                                SELECT carnet, a_o, mes, tipo_aportante FROM payroll_copy_regionals WHERE deleted_at IS NULL AND created_at::date = '".$date_import."' GROUP BY carnet, a_o, mes, tipo_aportante HAVING COUNT(*) > 1) AS subquery WHERE pcr.carnet = subquery.carnet AND pcr.a_o = subquery.a_o AND pcr.mes = subquery.mes AND pcr.tipo_aportante = subquery.tipo_aportante AND deleted_at IS NULL;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "UPDATE payroll_copy_regionals pcr SET error_message =
                                CASE 
                                    WHEN error_message IS NULL OR error_message = '' THEN
                                        'La contribución tiene error en la suma de renta pensión: cotizable + renta dignidad'
                                    ELSE
                                        error_message || ' - ' || 'La contribución tiene error en renta pensión, cotizable o renta dignidad'
                                END
                            FROM (
                                SELECT id FROM payroll_copy_regionals WHERE created_at::date = '".$date_import."' AND deleted_at IS NULL AND (COALESCE(cotizable,0) + COALESCE(renta_dignidad,0)) <> COALESCE(total_pension,0)) AS subquery WHERE pcr.id = subquery.id;";
                        $verify_data = DB::connection('db_aux')->select($verify_data);

                        $verify_data = "SELECT COUNT(id) from payroll_copy_regionals pcr WHERE created_at::date = '".$date_import."' AND error_message IS NOT NULL AND deleted_at IS NULL;";
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
                                'error' => 'El nombre del archivo no coincide con el nombre requerido',
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
        } catch(Exception $e){
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

    public function data_count_payroll_regional($date_import){
        $data_count['num_total_data_copy'] = 0;
        $data_count['num_data_not_considered'] = 0;
        $data_count['num_data_unrelated'] = 0;
        $data_count['num_data_considered'] = 0;
        $data_count['num_data_validated'] = 0;
        $data_count['num_data_not_validated'] = 0;

        // Total de datos del archivo
        $query_total_data = "SELECT COUNT(id) FROM payroll_copy_regionals WHERE created_at::date = '$date_import';";
        $query_total_data = DB::connection('db_aux')->select($query_total_data);
        $data_count['num_total_data_copy'] = $query_total_data[0]->count;

        // Número de datos no considerados duplicados de afiliados y aportes
        $query_data_not_considered = "SELECT COUNT(id) FROM payroll_copy_regionals WHERE created_at::date = '$date_import' AND (error_message IS NOT NULL OR deleted_at IS NOT NULL);";
        $query_data_not_considered = DB::connection('db_aux')->select($query_data_not_considered);
        $data_count['num_data_not_considered'] = $query_data_not_considered[0]->count;

        // Número de datos no relacionados
        $query_data_unrelated = "SELECT COUNT(id) FROM payroll_copy_regionals WHERE created_at::date = '$date_import' AND error_message IS NULL AND deleted_at IS NULL AND criteria = '9-no-identificado';";
        $query_data_unrelated = DB::connection('db_aux')->select($query_data_unrelated);
        $data_count['num_data_unrelated'] = $query_data_unrelated[0]->count;

        // Número de datos considerados
        $query_data_considered = "SELECT COUNT(id) FROM payroll_copy_regionals WHERE created_at::date = '$date_import' AND error_message IS NULL AND deleted_at IS NULL;";
        $query_data_considered = DB::connection('db_aux')->select($query_data_considered);
        $data_count['num_data_considered'] = $query_data_considered[0]->count;

        // Número de datos válidos
        $data_count['num_data_validated'] = PayrollRegional::data_period($date_import)['count_data'];

        return $data_count;
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/download_error_data_regional",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="DESCARGA EL ARCHIVO, CON EL LISTADO DE AFILIADOS QUE TENGAN OBSERVACIONES",
     *      operationId="download_error_data_regional",
     *      description="Descarga el archivo con el listado de afiliados con CI duplicado, primer nombre nulo, apellido paterno y materno en nulo",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *               @OA\Property(property="date_import", type="string", description="fecha importacion required", example= "2025-11-07")
     *            )
     *          ),
     *      ),
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
        $request->validate([
            'date_import' => 'required|date',
        ]);
        $date_import = Carbon::parse($request->date_import)->format('Y-m-d');
        
        $data_header=array(array("CARNET","TIPO APORTANTE","PRIMER NOMBRE","SEGUNDO NOMBRE","APELLIDO PATERNO","APELLIDO MATERNO","APELLIDO CASADA","NRO RECIBO","FECHA DEPOSITO","TOTAL DEPOSITADO","MES","AÑO","PENSION","RENTA DIGNIDAD","COTIZABLE","APORTE","%APORTE","OBSERVACIÓN"));

        $data_payroll_copy_regional = "SELECT carnet,tipo_aportante,nom,nom2,pat,mat,ap_casada,recibo,fecha_deposito,total_depositado,mes,a_o,total_pension,renta_dignidad,cotizable,aporte,porcentaje_aporte,error_message FROM payroll_copy_regionals pcr WHERE created_at::date = '".$date_import."' AND (error_message IS NOT NULL OR deleted_at IS NOT NULL) order by carnet";
        $data_payroll_copy_regional = DB::connection('db_aux')->select($data_payroll_copy_regional);
            foreach ($data_payroll_copy_regional as $row){
                array_push($data_header, array($row->carnet,$row->tipo_aportante,$row->nom,$row->nom2,$row->pat,$row->mat,$row->ap_casada,
                $row->recibo,$row->fecha_deposito,$row->total_depositado,$row->mes,$row->a_o,$row->total_pension,$row->renta_dignidad,
                $row->cotizable,$row->aporte,$row->porcentaje_aporte,$row->error_message));
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
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=false,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *              @OA\Property(property="date_import", type="string", description="fecha importación required", example= "2025-11-07")
     *          )
     *        ),
     *     ),
     *     security={
     *       {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           type="object"
     *       )
     *     )
     * )
     *
     * Logs user into the system.
     *
     * @param Request $request
     * @return void
     */
    public function validation_affiliate_regional(Request $request){
        $request->validate([
            'date_import' => 'required|date',
        ]);
        $date_import = Carbon::parse($request->date_import)->format('Y-m-d');
        try{
            DB::beginTransaction();
            $message = "No hay datos por validar.";
            $successfully = false;
            $data_count['num_total_data_copy'] = 0;
            $data_count['count_data_automatic_link'] = 0;
            $data_count['count_data_unidentified'] = 0;
            $data_count['count_data_error'] = 0;
            $data_count['num_total_data_payroll'] = 0;
            $data_count['num_total_data_contribution'] = 0;
            $route = '';
            $route_file_name = '';

            $connection_db_aux = Util::connection_db_aux();
            $query = "SELECT search_affiliate_regional('$connection_db_aux', '$date_import');";
            $data_validated = DB::select($query);
            $num_total_data_copy = $this->data_count_payroll_regional($date_import);
            $count_data_automatic_link = "SELECT COUNT(id) FROM payroll_copy_regionals pcr WHERE criteria IN ('1-CI-PN-SN-PA-SA-AC', '2-CI-sPN-sPA-sSA', '3-partCI-sPN-sPA', '4-sCI-PN-PA-SA', '5-CI-PN-SN-PA-SA-AC','6-CI-sPN-sPA-sSA','7-partCI-sPN-sPA','8-sCI-PN-PA-SA') AND created_at::date = '".$date_import."'";
            $count_data_automatic_link = DB::connection('db_aux')->select($count_data_automatic_link);
            $count_data_unidentified = "SELECT COUNT(id) FROM payroll_copy_regionals pcr WHERE criteria IN ('9-no-identificado') AND created_at::date = '".$date_import."'";
            $count_data_unidentified = DB::connection('db_aux')->select($count_data_unidentified);
            $count_data_error = "SELECT COUNT(id) FROM payroll_copy_regionals pcr WHERE (error_message IS NOT NULL OR deleted_at IS NOT NULL) AND created_at::date = '".$date_import."'";
            $count_data_error = DB::connection('db_aux')->select($count_data_error);
            $data_count['num_total_data_copy'] = $num_total_data_copy['num_total_data_copy'];
            $data_count['count_data_automatic_link'] = $count_data_automatic_link[0]->count;
            $data_count['count_data_unidentified'] = $count_data_unidentified[0]->count;
            $data_count['count_data_error'] = $count_data_error[0]->count;
            $validated_contribution = $this->validation_contribution_regional($date_import);

            if($num_total_data_copy['num_total_data_copy'] <= 0){
                $successfully = false;
                $message = 'no existen datos';
            }elseif($count_data_unidentified[0]->count > 0){
                $successfully = false;
                $message = 'Excel';
                $route = '/contribution/download_data_revision';
                $route_file_name = 'observados_para_revision.xls';
            }elseif($count_data_unidentified[0]->count == 0 && $count_data_error[0]->count > 0){
                $valid_contribution = "SELECT COUNT(id) FROM payroll_copy_regionals pcr WHERE state LIKE 'accomplished' AND error_message IS NOT NULL AND created_at::date = '".$date_import."';";
                $valid_contribution = DB::connection('db_aux')->select($valid_contribution);
                if($valid_contribution[0]->count == 0){
                    $successfully = true;
                    $message = 'Excel';
                    $route = '/contribution/download_data_revision';
                    $route_file_name = 'afiliados_para_creacion.xls';
                }else{
                    $successfully = false;
                    $message = 'Excel';
                    $route = '/contribution/download_error_data_archive';
                    $route_file_name = 'datos_aportes_observados.xls';
                }
            }elseif($count_data_unidentified[0]->count == 0 && $count_data_error[0]->count == 0){
                $successfully = true;
                $message = 'Realizado con éxito.';
            }else{
                $successfully = false;
                $message = 'Ops ocurrió algo inesperado.';
            }
            DB::commit();
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

    // Método para verificar si existe montos con diferentes contribuciones
    public function validation_contribution_regional($date_import){
        $different_contribution = false;
        $connection_db_aux = Util::connection_db_aux();
        // Reemplaza los casos que tengan aportes iguales registrados desde la planilla de regionales
        // Reemplaza los valores que contengan cero en aporte aunque estén clasificados

        // Consulta para traer los registros con diferencias en el aporte
        $sql_dblink = "
            SELECT id, affiliate_id, a_o, mes, aporte, created_at
            FROM payroll_copy_regionals
            WHERE created_at::date = '$date_import'
        ";
        
        $payroll_regional = DB::select("SELECT pcr.id, cp.affiliate_id, pcr.aporte, cp.total, cp.contribution_type_mortuary_id
            FROM contribution_passives cp
            JOIN dblink('$connection_db_aux', $$ $sql_dblink $$)
            AS pcr(id INT, affiliate_id INT, a_o INT, mes INT, aporte NUMERIC(13,2), created_at date)
            ON cp.affiliate_id = pcr.affiliate_id
            WHERE EXTRACT(YEAR FROM cp.month_year) = pcr.a_o
            AND EXTRACT(MONTH FROM cp.month_year) = pcr.mes
            AND cp.total <> pcr.aporte
            AND cp.total > 0
            AND pcr.created_at::date = '$date_import';
        ");

        foreach($payroll_regional as $update_payroll) {
            $messages = [];
            if ($update_payroll->total != $update_payroll->aporte) {
                $messages[] = "La contribución anterior es: $update_payroll->total difiere de la planilla $update_payroll->aporte";
            }
            if (!empty($messages)) {
                $error_message = implode(' - ', $messages);
                DB::connection('db_aux')->update("
                UPDATE payroll_copy_regionals pcr
                SET error_message = 
                    COALESCE(NULLIF(error_message, ''), '') ||
                    CASE 
                        WHEN error_message IS NULL OR error_message = '' THEN '' 
                        ELSE ' - ' 
                    END ||
                    ?
                WHERE pcr.id = ?
                  AND created_at::date = ?
                ", [
                    $error_message,
                    $update_payroll->id,
                    $date_import
                ]);
                $different_contribution = true;
            }
        }
        return !$different_contribution;
    }

        /**
     * @OA\Post(
     *      path="/api/contribution/import_payroll_regional",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="PASO 3 VALIDACIÓN DE DATOS APORTES",
     *      operationId="validation_contribution_regional",
     *      description="Validación de datos de aportes de payroll_copy_regionals a la tabla payroll_regionals",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *              @OA\Property(property="date_import", type="string", description="fecha importación required", example= "2025-11-07")
     *            )
     *          ),
     *     ),
     *     security={
     *          {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="object"
     *         )
     *     )
     * )
     *
     * Logs user into the system.
     *
     * @param Request $request
     * @return void
     */
    public function import_payroll_regional(Request $request){
        $request->validate([
            'date_import' => 'required|date',
        ]);
        $date_import = Carbon::parse($request->date_import)->format('Y-m-d');
        try{
            DB::beginTransaction();
            $message = "No hay datos";
            $successfully = false;
            $connection_db_aux = Util::connection_db_aux();

            // Conteo de  affiliate_id is null distinto del criterio 9-no-identificado
            $count_data = "SELECT COUNT(id) FROM payroll_copy_regionals WHERE error_message IS NULL AND deleted_at IS NULL AND state = 'accomplished' AND affiliate_id IS NOT NULL AND criteria!='9-no-identificado' AND created_at::date = '".$date_import."';";
            $count_data = DB::connection('db_aux')->select($count_data);
            if ($count_data[0]->count > 0){
                $count_data_validated = "SELECT COUNT(id) FROM payroll_copy_regionals WHERE state ='validated' AND created_at::date = '".$date_import."';";
                $count_data_validated = DB::connection('db_aux')->select($count_data_validated);

                if ($count_data_validated[0]->count == 0){
                    $query = "SELECT registration_payroll_regionals('$connection_db_aux', '$date_import');";
                    $data_validated = DB::select($query);
                    if ($data_validated){
                        $message = "Realizado con éxito";
                        $successfully = true;
                        $data_payroll_copy_regional = "SELECT * FROM payroll_copy_regionals WHERE state = 'validated' AND created_at::date = '".$date_import."';";
                        $data_payroll_copy_regional = DB::connection('db_aux')->select($data_payroll_copy_regional);
                        if (count($data_payroll_copy_regional)> 0){
                            $message = "Excel";                          
                        }
                    }
                    DB::commit();
                    $data_count = $this->data_count_payroll_regional($date_import);
                    return response()->json([
                        'message' => $message,
                        'payload' => [
                            'successfully' => $successfully,
                            'data_count' =>  $data_count
                        ],
                    ]);
                }else{
                    $errorMessage = 'Error, ya realizó la validación de datos.';
                    return response()->json([
                        'message' => $errorMessage,
                        'payload' => [
                            'successfully' => $successfully,
                            'error' => $errorMessage
                        ],
                    ]);
                }
            }else{
                return response()->json([
                    'message' => "Error no existen datos en la tabla del copiado de datos",
                    'payload' => [
                        'successfully' => $successfully,
                        'error' => 'Error el primer paso no está concluido o se concluyó el paso 3.'
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

    /**
     * @OA\Post(
     *      path="/api/contribution/import_contribution_regional",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="PASO 4 IMPORTACIÓN DE CONTRIBUCIONES REGIONAL",
     *      operationId="import_contribution_regional",
     *      description="Importación de aportes de regional a la tabla contribution_passives",
     *      @OA\RequestBody(
     *          description="Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_import", type="string", description="fecha importación required", example= "2025-11-07")
     *            )
     *          ),
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *            type="object"
     *         )
     *     )
     * )
     *
     * Logs user into the system.
     *
     * @param Request $request
     * @return void
     */
    public function import_contribution_regional(Request $request){
        $request->validate([
            'date_import' => 'required|date',
        ]);
        try {
            DB::beginTransaction();
            $userId = Auth::id();
            $date_import = Carbon::parse($request->date_import)->format('Y-m-d');
            $message = 'No existen datos de la planilla.';
            $success = false;

            // Verifica si ya se realizó una importación
            $existingContributions = DB::table('contribution_passives')
                ->where('contributionable_type', 'payroll_regionals')
                ->whereDate('created_at', '=', $date_import)
                ->count();

            if ($existingContributions > 0) {
                return response()->json([
                    'message' => 'Error, ya se realizó la importación de datos.',
                    'payload' => [
                        'successfully' => false,
                        'num_total_data_contribution' => $existingContributions,
                    ],
                ]);
            }
            // Verifica si hay datos en payroll_regionals
            $payrollCount = DB::table('payroll_regionals')->whereDate('created_at', '=', $date_import)->count();
            
            if ($payrollCount > 0) {
                DB::statement("SELECT import_contribution_regional($userId, '$date_import')");
                DB::commit();
                $message = 'Importación realizada con éxito.';
                $success = true;

                $totalContributions = DB::table('contribution_passives')
                   ->where('contributionable_type', 'payroll_regionals')
                   ->whereDate('created_at', '=', $date_import)
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

    /**
     * @OA\Post(
     *      path="/api/contribution/download_data_regional",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="DESCARGA ARCHIVO DE REGISTROS CORRECTOS Y OBSERVADOS",
     *      operationId="download_data_regional",
     *      description="Genera un reporte con los registros de payroll_copy_regionals filtrados por tipo: correctos u observados para el date_import dado.",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="date_import",
     *                      type="string",
     *                      description="fecha importación required",
     *                      example="2025-11-07"
     *                  ),
     *                  @OA\Property(
     *                      property="type",
     *                      type="string",
     *                      description="Tipo de registros a descargar: correcto u observado",
     *                      example="correcto"
     *                  )
     *              )
     *          ),
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      },
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="string",
     *              example="Archivo Excel generado"
     *          )
     *      )
     * )
     *
     * @param Request $request
     * @return void
     */
    public function download_data_regional(Request $request)
    {
        $request->validate([
            'date_import'=>'required|date',
            'type'=>'nullable|string|in:correcto,observado',
        ]);

        $date_import = Carbon::parse($request->date_import)->format('Y-m-d');
        $type = $request->input('type', 'correcto'); 
        // Encabezados del archivo
        $base_header = [
            "CARNET",
            "TIPO APORTANTE",
            "PRIMER NOMBRE",
            "SEGUNDO NOMBRE",
            "APELLIDO PATERNO",
            "APELLIDO MATERNO",
            "APELLIDO CASADA",
            "NRO RECIBO",
            "FECHA DEPOSITO",
            "TOTAL DEPOSITADO",
            "MES",
            "AÑO",
            "PENSION",
            "RENTA DIGNIDAD",
            "COTIZABLE",
            "APORTE",
            "%APORTE",
        ];

        if ($type === 'correcto') {
            // SOLO CORRECTOS 
            $data_header = [ $base_header ];
            $where = "
                created_at::date = '".$date_import."'
                AND error_message IS NULL
                AND criteria != '9-no-identificado'
                AND affiliate_id IS NOT NULL 
                AND affiliate_id != 0
                AND deleted_at IS NULL
                AND state ILIKE 'validated'
            ";
            $sql = "
                SELECT
                    carnet,
                    tipo_aportante,
                    nom,
                    nom2,
                    pat,
                    mat,
                    ap_casada,
                    recibo,
                    fecha_deposito,
                    total_depositado,
                    mes,
                    a_o,
                    total_pension,
                    renta_dignidad,
                    cotizable,
                    aporte,
                    porcentaje_aporte
                FROM payroll_copy_regionals
                WHERE $where
                ORDER BY carnet, a_o, mes
            ";
            $rows = DB::connection('db_aux')->select($sql);
            foreach ($rows as $row) {
                $data_header[] = [
                    $row->carnet,
                    $row->tipo_aportante,
                    $row->nom,
                    $row->nom2,
                    $row->pat,
                    $row->mat,
                    $row->ap_casada,
                    $row->recibo,
                    $row->fecha_deposito,
                    $row->total_depositado,
                    $row->mes,
                    $row->a_o,
                    $row->total_pension,
                    $row->renta_dignidad,
                    $row->cotizable,
                    $row->aporte,
                    $row->porcentaje_aporte,
                ];
            }
        } else {
            // SOLO OBSERVADOS
            $data_header = [
                array_merge($base_header, [
                    "AFFILIATE_ID",
                    "CRITERIA",
                    "DELETED_AT",
                    "ERROR_MESSAGE",
                ])
            ];
            $where = "
                created_at::date = '".$date_import."'
                AND (
                    error_message IS NOT NULL
                    OR criteria ILIKE '9-no-identificado'
                    OR affiliate_id = 0
                    OR deleted_at IS NOT NULL
                    OR state ILIKE 'accomplished'
                )
            ";

            $sql = "
                SELECT
                    carnet,
                    tipo_aportante,
                    nom,
                    nom2,
                    pat,
                    mat,
                    ap_casada,
                    recibo,
                    fecha_deposito,
                    total_depositado,
                    mes,
                    a_o,
                    total_pension,
                    renta_dignidad,
                    cotizable,
                    aporte,
                    porcentaje_aporte,
                    affiliate_id,
                    criteria,
                    deleted_at,
                    error_message
                FROM payroll_copy_regionals
                WHERE $where
                ORDER BY carnet, a_o, mes
            ";

            $rows = DB::connection('db_aux')->select($sql);

            foreach ($rows as $row) {
                $data_header[] = [
                    $row->carnet,
                    $row->tipo_aportante,
                    $row->nom,
                    $row->nom2,
                    $row->pat,
                    $row->mat,
                    $row->ap_casada,
                    $row->recibo,
                    $row->fecha_deposito,
                    $row->total_depositado,
                    $row->mes,
                    $row->a_o,
                    $row->total_pension,
                    $row->renta_dignidad,
                    $row->cotizable,
                    $row->aporte,
                    $row->porcentaje_aporte,
                    $row->affiliate_id,
                    $row->criteria,
                    $row->deleted_at,
                    $row->error_message,
                ];
            }
        }

        $export = new ArchivoPrimarioExport($data_header);
        $file_name = "regional_" . $type . "_" . $date_import;
        $extension = '.xls';

        return Excel::download($export, $file_name.$extension);
    }

    /**
     * @OA\Post(
     *      path="/api/contribution/rollback_payroll_copy_regionals",
     *      tags={"IMPORTACIÓN-PLANILLA-REGIONAL"},
     *      summary="REHACER IMPORTACIÓN PLANILLA REGIONAL",
     *      operationId="rollback_payroll_copy_regional",
     *      description="Para rehacer la importación de planilla regional",
     *      @OA\RequestBody(
     *          description="Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_payroll", type="string",description="fecha de planilla required",example= "2025-11-07")
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
    public function rollback_payroll_copy_regionals(Request $request)
    {
        $request->validate([
            'date_import' => 'required|date_format:"Y-m-d"',
        ]);
        DB::beginTransaction();
        try{
            $result['delete_step_1'] = false;
            $valid_rollback = false;
            $date_import = Carbon::parse($request->date_import)->format('Y-m-d');

            if($this->exists_data_payroll_copy_regionals($date_import) && !PayrollRegional::data_period($date_import)['exist_data']){
                $result['delete_step_1'] = $this->delete_payroll_copy_regionals($date_import);

                if($result['delete_step_1'] == true){
                    $valid_rollback = true;
                    $message = "Realizado con éxito!";
                }
            }else{
                if(PayrollRegional::data_period($date_import)['exist_data'])
                    $message = "No se puede rehacer, porque ya realizó la validación de la planilla.";
                else
                    $message = "No existen datos para rehacer.";
            }

            DB::commit();

            return response()->json([
                'message' => $message,
                'payload' => [
                    'valid_rollback' => $valid_rollback,
                    'delete_step' => $result
                ],
            ]);
        }catch (Exception $e)
        {
            DB::rollback();
            return $e;
        }
    }

    //borrado de datos de la tabla payroll_copy_commands paso 1
    public function delete_payroll_copy_regionals($date_import)
    {
        if ($this->exists_data_payroll_copy_regionals($date_import)) {
            $query = "SELECT COUNT(id) FROM payroll_copy_regionals WHERE created_at::date = '$date_import';";
            $query = DB::connection('db_aux')->select($query);
            DB::commit();
            return true;
        } else
            return false;
    }

    //método para verificar si existe datos en el paso 1 
    public function exists_data_payroll_copy_regionals($date_import)
    {
        $exists_data = true;
        $query = "SELECT * FROM payroll_copy_regionals WHERE created_at::date = '$date_import';";
        $verify_data = DB::connection('db_aux')->select($query);

        if ($verify_data == []) $exists_data = false;

        return $exists_data;
    }
}
