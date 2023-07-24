<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Exports\ArchivoPrimarioExport;
use Illuminate\Database\QueryException;
use App\Helpers\Util;
use Auth;
use App\Models\Affiliate\Affiliate;
use Maatwebsite\Excel\Facades\Excel;

class ImportAffiliatesController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/affiliate/upload_copy_affiliates_availability",
     *      tags={"IMPORTACION-AFILIADOS-DISPONIBILIDAD"},
     *      summary="PASO 1 COPIADO DE DATOS AFILIADOS DISPONIBILIDAD",
     *      operationId="upload_copy_affiliates_availability",
     *      description="Copiado de datos del archivo de afiliados en disponibilidad",
     *      @OA\RequestBody(
     *          description="Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data",@OA\Schema(
     *              @OA\Property(property="file", type="file", description="file required", example="file"),
     *              @OA\Property(property="date_import", type="string", description="Fecha de importación required", example="2023-01-01"),
     *              )
     *          ),
     *      ),
     *      security={
     *          {"bearerAuth":{}}
     *      },
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *             type="object"
     *          )
     *      )
     * )
    */
    public function upload_copy_affiliates_availability(request $request) {

        $request->validate([
            'file' => 'required',
            'date_import' => 'required|date_format:"Y-m-d'
        ]);
        $extension = strtolower($request->file->getClientOriginalExtension());
        $file_name_entry = $request->file->getClientOriginalName();
        $route = '';
        $route_file_name =  '';
        $message = "Sin datos";
        DB::beginTransaction();
        try {
            $username = env('FTP_USERNAME');
            $password = env('FTP_PASSWORD');
            $successfully = false;
            if($extension == "csv") { // Si es un archivo csv
                $date_import = Carbon::parse($request->date_import);
                $year = $date_import->format("Y");
                $year_format = $date_import->format("y");
                $month = $date_import->format("m");
                $month_format = (int)$month;

                $rollback_period = "DELETE FROM copy_affiliates_availability WHERE mes = $month_format and a_o = $year;";
                DB::connection('db_aux')->select($rollback_period);
                $file_name = "disponibilidad-".$month."-".$year.".".$extension;
                if($file_name_entry == $file_name) { // Si tiene el mismo nombre
                    // Carga el archivo
                    $base_path = 'afiliados/afiliados_disponibilidad/'.$month.'-'.$year;
                    $file_path = Storage::disk('ftp')->putFileAs($base_path, $request->file, $file_name);
                    $base_path = 'ftp://'.env('FTP_HOST').env('FTP_ROOT').$file_path;

                    $drop = "DROP TABLE IF EXISTS affiliates_in_availability_tmp";
                    $drop = DB::connection('db_aux')->select($drop);

                    // Tabla temporal
                    $temporay_availability = "CREATE TEMPORARY TABLE affiliates_in_availability_tmp (
                                                cedula varchar, grado varchar, paterno varchar, materno varchar,
                                                nombres varchar, situacion_laboral varchar, unidad varchar);";
                    DB::connection('db_aux')->select($temporay_availability);

                    // Copiado del archvo
                    $copy = "COPY affiliates_in_availability_tmp(cedula, grado, paterno, materno, nombres, situacion_laboral, unidad)
                            FROM PROGRAM 'wget -q -O - $@  --user=$username --password=$password $base_path'
                            WITH DELIMITER ':' CSV HEADER;";
                    $copy = DB::connection('db_aux')->select($copy);

                    $check_quantity = "SELECT COUNT(*) FROM affiliates_in_availability_tmp";
                    $quantity = DB::connection('db_aux')->select($check_quantity);

                    if($check_quantity == 0) {
                        return response()->json([
                            'message' => 'No hay datos para importar',
                            'payload' => [
                                'successfuly' => false,
                            ]
                            ], 204);
                    }

                    $insert = $this->separate_names($month, $year);
                    $drop = 'DROP TABLE if EXISTS affiliates_in_availability_tmp';
                    DB::connection('db_aux')->select($drop);

                    $verify_data = "UPDATE copy_affiliates_availability caa SET error_mensaje = concat(error_mensaje, ' - ', 'El valor del cedula es NULO') FROM (SELECT id FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND (cedula IS NULL OR cedula LIKE '')) AS subquery WHERE caa.id = subquery.id;";
                    $verify_data = DB::connection('db_aux')->select($verify_data);

                    $verify_data = "UPDATE copy_affiliates_availability caa SET error_mensaje = concat(error_mensaje, ' - ', 'El valor del primer nombre es NULO') FROM (SELECT id FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND (primer_nombre IS NULL OR primer_nombre LIKE '')) AS subquery WHERE caa.id = subquery.id;";
                    $verify_data = DB::connection('db_aux')->select($verify_data);

                    $verify_data = "UPDATE copy_affiliates_availability caa SET error_mensaje = concat(error_mensaje, ' - ', 'El número de carnet es duplicado') FROM (SELECT cedula, count(cedula) FROM copy_affiliates_availability WHERE  mes = $month AND a_o = $year GROUP BY cedula HAVING count(cedula) > 1) AS subquery WHERE caa.cedula = subquery.cedula;";
                    $verify_data = DB::connection('db_aux')->select($verify_data);

                    $verify_data = "SELECT count(id) FROM copy_affiliates_availability caa WHERE mes = $month AND a_o= $year AND error_mensaje is not null;";
                    $verify_data = DB::connection('db_aux')->select($verify_data);

                    if($verify_data[0]->count > 0) {
                        $route = '/affiliate/download_error_data_archive';
                        $route_file_name = 'datos_observados_archivo.xls';
                        return response()->json([
                            'message' => 'Excel',
                            'payload' => [
                                'successfully' => false,
                                'error' => 'Existen datos en el archivo que son incorrectos, favor revisar.',
                                'route' =>$route,
                                'route_file_name' => $route_file_name
                            ]
                        ]);
                    }
                    DB::commit();

                    $query_total_data = "SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month::INTEGER AND a_o = $year::INTEGER;";
                    $query_total_data = DB::connection('db_aux')->select($query_total_data);
                    if($query_total_data[0]->count > 0) {
                        $message = "Realizado con éxito";
                        $successfully = true;
                    }

                    return response()->json([
                        'message' => $message,
                        'payload' => [
                            'successfully' => $successfully,
                            'route' => $route,
                            'route_file_name' => $route_file_name,
                            'data_count' => $query_total_data[0]
                        ]
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Error en el copiado del archivo',
                        'payload' => [
                            'successfully' => $successfully,
                            'error' => 'El nombre del archivo no coincide con en nombre requerido',
                            'route' => $route,
                            'route_file_name' => $route_file_name
                        ]
                        ], 404);
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
                ], 404);
            }
        } catch(QueryException $e) {
            $message = $e->getMessage();
            logger($message);
            if(strpos($message, 'extra') !== false) $message = "Hay más columnas de las esperadas";
            elseif (strpos($message, 'DETAIL:') !== false) {
                $clipped_chain = substr($message, strrpos($message, "DETAIL:  ") + 9);
                $end_of_chain = substr($clipped_chain, strrpos($clipped_chain, "CONTEXT"));
                $message = substr($clipped_chain, 0, -strlen($end_of_chain));
            }
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error',
                'payload' => [
                    'sucessfully' => false,
                    'error' => $message
                ]
            ], 500);
        } catch(\Exception $e) {
            logger("entra aca");
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error',
                'payload' => [
                    'sucessfully' => false
                ]
            ], 500);
        }
    }

    public function separate_names($month, $year) {
        $function = "CREATE OR REPLACE FUNCTION create_temp_with_names_separator() RETURNS boolean
            AS
            $$
            DECLARE
                first_name varchar := NULL;
                second_name varchar := NULL;
                record RECORD;
            BEGIN
                FOR record IN SELECT *
                        FROM affiliates_in_availability_tmp
                LOOP
                    first_name := NULL;
                    second_name := NULL;
                    IF (SELECT record.nombres LIKE '% %' AS flag) THEN
                        SELECT split_part(record.nombres, ' ', 1) INTO first_name;
                        SELECT split_part(record.nombres, ' ', 2) INTO second_name;
                    ELSE
                        first_name := record.nombres;
                    END IF;
                    INSERT INTO copy_affiliates_availability (cedula, grado, paterno, materno, primer_nombre, segundo_nombre, situacion_laboral, unidad, mes, a_o) VALUES (record.cedula, record.grado, record.paterno, record.materno, first_name, second_name, record.situacion_laboral, record.unidad, $month, $year);
                END LOOP;

                RETURN true;
            END;
            $$ LANGUAGE plpgsql;";
        DB::connection('db_aux')->select($function);
        DB::connection('db_aux')->select("SELECT create_temp_with_names_separator();");
    }

    /**
      * @OA\Post(
      *      path="/api/affiliate/download_error_data_archive",
      *      tags={"IMPORTACION-AFILIADOS-DISPONIBILIDAD"},
      *      summary="DESCARGA EL ARCHIVO, CON EL LISTADO DE AFILIADOS QUE TENGAN OBSERVACIONES EN EL ARCHIVO",
      *      operationId="download_error_data_archive_availability",
      *      description="Descarga el archivo con el listado de afiliados con CI duplicado, primer nombre nulo, apellido paterno y materno en nulo ",
      *      @OA\RequestBody(
      *          description= "Provide auth credentials",
      *          required=true,
      *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
      *             @OA\Property(property="date_import", type="string", description="fecha de importación required", example= "2023-10-01")
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
    public function download_error_data_archive(Request $request) {
        try {
            $request->validate([
                'date_import' => 'required|date_format:"Y-m-d"',
            ]);
            $data_header = array(array("CEDULA", "GRADO", "PATERNO", "MATERNO", "NOMBRES", "SITUACIÓN LABORAL", "UNIDAD"));
            $date_import = Carbon::parse($request->date_import);
            $year = (int)$date_import->format("Y");
            $month = (int)$date_import->format("m");
            $data_affiliates_availability = "SELECT cedula, grado, paterno, materno, primer_nombre, segundo_nombre, situacion_laboral, unidad FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND (error_mensaje = '' OR error_mensaje is NOT NULL) ORDER BY cedula";
            $data_affiliates_availability = DB::connection('db_aux')->select($data_affiliates_availability);
            foreach($data_affiliates_availability as $row) {
                array_push($data_header, array($row->cedula, $row->grado, $row->paterno, $row->materno, ($row->primer_nombre .' '.$row->segundo_nombre), $row->situacion_laboral, $row->unidad));
            }
            $export = new ArchivoPrimarioExport($data_header);
            $file_name = "observación-afiliados-disponibilidad";
            $extension = '.xls';
            return Excel::download($export, $file_name."_".$month."_".$year.$extension);
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Hubo un error al generar el archivo',
                'payload' => [
                    'successfull' => false
                ]
            ], 500)->header('Content-Disposition', 'attachment; filename="error.json"');
        }
    }

    /**
     * @OA\Post(
     *      path="/api/affiliate/validate_availability",
     *      tags={"IMPORTACION-AFILIADOS-DISPONIBILIDAD"},
     *      summary="PASO 2 VALIDACION AFILIADOS DISPONIBILIDAD",
     *      operationId="validation_affiliate_availability",
     *      description="Actualización de Afiliados en disponibilidad",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_import", type="string",description="fecha de importación required",example= "2023-01-01")
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
    public function validate_affiliates_availability(Request $request) {
        $request->validate([
            'date_import' => 'required|date_format:"Y-m-d"',
        ]);

        try {
            DB::beginTransaction();
            $message = "No hay datos para validar.";
            $successfully = false;
            $route = '';
            $route_file_name = '';
            $date_import = Carbon::parse($request->date_import);
            $year = (int)$date_import->format("Y");
            $month = (int)$date_import->format("m");

            $connection_db_aux = Util::connection_db_aux();

            $query = "SELECT search_affiliate_availability('$connection_db_aux', $month, $year);";
            $data_validated = DB::select($query);

            $count_data_creation = "SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND criteria in ('6-CREAR')";
            $count_data_creation = DB::connection('db_aux')->select($count_data_creation);

            $validation = "SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND error_mensaje IS NOT NULL AND state NOT LIKE 'accomplished'";
            $validation = DB::connection('db_aux')->select($validation);

            if($count_data_creation[0]->count != 0) {
                return response()->json([
                    'message' => "No se encontraron a algunos afiliados",
                    'payload' => [
                        'successfully' => false,
                        'route' => '/affiliate/download_data_revision',
                        'route_file_name' => 'observador_para_revision.xls',
                        'data_count' => $validation
                    ]
                ]);
            }

            if($validation[0]->count != 0){
                return response()->json([
                    'message' => "No se encontraron a algunos afiliados",
                    'payload' => [
                        'successfully' => false,
                        'route' => '/affiliates/download_data_revision',
                        'route_file_name' => 'observador_para_revision.xls'
                    ]
                ]);
            }

            if($this->update_availability_status($month, $year)) {
                DB::commit();
                return response()->json([
                    'message' => "Actualización exitosa",
                    'payload' => [
                        'successfully' => true,
                        'data_count' => $this->data_count($month, $year),
                        'route' => '/affiliates/download_data_revision_suggestion',
                        'route_file_name' => 'observados_para_revision.xls'
                    ],
                ]);
            }
            return response()->json([
                'message' => $message,
                'payload' => [
                    'successfully' => $successfully,
                    'route' => $route,
                    'route_file_name' => $route_file_name
                ],
            ]);

        } catch(QueryException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error de Base de Datos',
                'payload' => [
                    'successfully' => false,
                ]
            ], 500);
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error en la búsqueda de afiliados.',
                'payload' => [
                    'successfully' => false,
                ]
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/affiliate/download_data_revision_suggestion",
     *      tags={"IMPORTACION-AFILIADOS-DISPONIBILIDAD"},
     *      summary="DESCARGA EL ARCHIVO, PARA SUGERIR LA REVISIÓN DE DATOS DE AFILIADOS",
     *      operationId="download_data_revision_suggestion_availability",
     *      description="Descarga el archivo, para la revisión del afiliado que no figura disponibilidad en el campo situación laboral",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_import", type="string", description="fecha de importación required", example= "2023-01-01")
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
    public function download_data_revision_suggestion(Request $request) {
        try {
            $request->validate([
                'date_import' => 'required|date_format:"Y-m-d"',
            ]);
            $data_header = array(array("CEDULA", "GRADO", "PATERNO", "MATERNO", "NOMBRES", "SITUACIÓN LABORAL", "UNIDAD", "DETALLE"));
            $date_import = Carbon::parse($request->date_import);
            $year = (int)$date_import->format("Y");
            $month = (int)$date_import->format("m");
            $data_affiliates_availability = "SELECT cedula, grado, paterno, materno, primer_nombre, segundo_nombre, situacion_laboral, unidad, 'Revisar disponibilidad' AS detalle
            FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND error_mensaje LIKE 'NO ACTUALIZADO'";
            $data_affiliates_availability = DB::connection('db_aux')->select($data_affiliates_availability);
            foreach($data_affiliates_availability as $row) {
                array_push($data_header, array($row->cedula, $row->grado, $row->paterno, $row->materno, ($row->primer_nombre .' '.$row->segundo_nombre), $row->situacion_laboral, $row->unidad, $row->detalle));
            }
            $export = new ArchivoPrimarioExport($data_header);
            $file_name = "sugerencia-afiliados-disponibilidad";
            $extension = '.xls';
            return Excel::download($export, $file_name."_".$month."_".$year.$extension);
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Hubo un error al generar el archivo',
                'payload' => [
                    'successfull' => false
                ]
            ], 500)->header('Content-Disposition', 'attachment; filename="error.json"');
        }
    }

    /**
     * @OA\Post(
     *      path="/api/affiliate/download_data_revision",
     *      tags={"IMPORTACION-AFILIADOS-DISPONIBILIDAD"},
     *      summary="DESCARGA EL ARCHIVO, PARA LA REVISIÓN DE DATOS DE LOS AFILIADOS",
     *      operationId="download_data_revision_availability",
     *      description="Descarga el archivo, para la revisión de datos de los afiliados no identificados",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_import", type="string", description="fecha de importación required", example= "2023-01-01")
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
    public function download_data_revision(Request $request) {
        try{
            $request->validate([
                'date_import' => 'required|date_format:"Y-m-d"',
            ]);
            $data_header = array(array("CEDULA", "GRADO", "PATERNO", "MATERNO", "NOMBRES", "SITUACIÓN LABORAL", "UNIDAD", "DETALLE PARA REVISIÓN"));
            $date_import = Carbon::parse($request->date_import);
            $year = (int)$date_import->format("Y");
            $month = (int)$date_import->format("m");
            $data_affiliates_availability = "SELECT cedula, grado, paterno, materno, primer_nombre, segundo_nombre, situacion_laboral, unidad, 
            (CASE WHEN (criteria = '6-CREAR') THEN 'IDENTIFICADO PARA SUBSANAR' END) as criteria FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND criteria IN ('6-CREAR') ORDER BY cedula";
            $data_affiliates_availability = DB::connection('db_aux')->select($data_affiliates_availability);
            foreach($data_affiliates_availability as $row) {
                array_push($data_header, array($row->cedula, $row->grado, $row->paterno, $row->materno, ($row->primer_nombre .' '.$row->segundo_nombre), $row->situacion_laboral, $row->unidad, $row->criteria));
            }
            $export = new ArchivoPrimarioExport($data_header);
            $file_name = "observación-afiliados-revision";
            $extension = '.xls';
            return Excel::download($export, $file_name."_".$month."_".$year.$extension);
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Hubo un error al generar el archivo',
                'payload' => [
                    'successfull' => false
                ]
            ], 500)->header('Content-Disposition', 'attachment; filename="error.json"');
        }
    }

    public static function update_availability_status($month, $year) {
        $count = 0;
        $affiliates = DB::connection('db_aux')->select("SELECT affiliate_id FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND (situacion_laboral LIKE '%DISPONIBILIDAD%' OR situacion_laboral LIKE '%DISP.%' OR situacion_laboral LIKE '%CATEGORIA%')");
        foreach($affiliates as $affiliate) {
            $link = "UPDATE affiliates SET affiliate_state_id = 3 WHERE id = $affiliate->affiliate_id";
            $link = DB::select($link);
            $count++;
        }
        $update_message = "UPDATE copy_affiliates_availability SET error_mensaje = 'NO ACTUALIZADO' WHERE mes = $month AND a_o = $year AND situacion_laboral NOT LIKE '%DISPONIBILIDAD%' AND situacion_laboral NOT LIKE '%DISP.%' AND situacion_laboral NOT LIKE '%CATEGORIA%'";
        $update_message = DB::connection('db_aux')->select($update_message);
        $affiliates_not_updated = DB::connection('db_aux')->select("SELECT count(*) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND situacion_laboral NOT LIKE '%DISPONIBILIDAD%' AND situacion_laboral NOT LIKE '%DISP.%' AND situacion_laboral NOT LIKE '%CATEGORIA%'");
        $amount = DB::connection('db_aux')->select("SELECT count(*) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year");
        $total = $amount[0]->count - $affiliates_not_updated[0]->count;
        if($total == $count) {
            return true;
        } else return false;
    }

    /**
     * @OA\Post(
     *      path="/api/affiliate/list_months_import_affiliates_availability",
     *      tags={"IMPORTACION-AFILIADOS-DISPONIBILIDAD"},
     *      summary="LISTA LOS MESES QUE SE REALIZARON IMPORTACIONES DE AFILIADOS EN DISPONIBILIDAD",
     *      operationId="list_months_import_affiliates_availability",
     *      description="Lista los meses importados de afiliados en disponibilidad",
     *     @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *             @OA\Property(property="period_year", type="integer",description="Año de importación de afiliados en disponibilidad",example= "2023"),
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
    public function list_months_import_affiliates_availability(Request $request) {
        $request->validate([
            'period_year' => 'required|date_format:"Y"',
            'with_data_count' => 'boolean'
        ]);
        $with_data_count = !isset($request->with_data_count) || is_null($request->with_data_count) ? true : $request->with_data_count;
        $period_year = $request->period_year;
        $query = "SELECT DISTINCT mes FROM copy_affiliates_availability WHERE deleted_at IS NULL AND a_o = $period_year AND affiliate_id IS NOT NULL";
        $periods = collect(DB::connection('db_aux')->select($query));
        $periods = $periods->pluck('mes');
        $months = collect(DB::select("SELECT id as period_month, name as period_month_name FROM months ORDER BY id ASC"));
        $months_ids = $months->pluck('period_month');

        $months_not_import = $months_ids->diff($periods);
        $months_import = $months_ids->intersect($periods);

        $months_not_import_with_name = $months->whereIn('period_month', $months_not_import)->values();
        $months_import_with_name = $months->whereNotIn('period_month', $months_not_import)->values();

        if($with_data_count) {
            foreach($months_import_with_name->all() as $months_import) {
                $months_import->data_count = $this->data_count($months_import->period_month, $period_year);
            }
        }

        return response()->json([
            'message' => "Exito",
            'payload' => [
                'list_months' => $months_import_with_name->all(),
                'list_months_not_import' => $months_not_import_with_name->all(),
            ]
        ]);
    }

    public function data_count($month, $year) {
        $query_total_data = "SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year";
        $query_total_data = DB::connection('db_aux')->select($query_total_data);

        $query_update_affiliates = "SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND error_mensaje IS NULL";
        $query_update_affiliates = DB::connection('db_aux')->select($query_update_affiliates);

        $query_no_update_affiliates = "SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND error_mensaje = 'NO ACTUALIZADO'";
        $query_no_update_affiliates = DB::connection('db_aux')->select($query_no_update_affiliates);

        $data_count['num_of_affiliates_updated'] = $query_update_affiliates[0]->count;
        $data_count['num_of_affiliates_not_updated'] = $query_no_update_affiliates[0]->count;
        $data_count['num_total_data_copy'] = $query_total_data[0]->count;

        return $data_count;
    }

    /**
     * @OA\Post(
     *      path="/api/affiliate/rollback_import_affiliates_availability",
     *      tags={"IMPORTACION-AFILIADOS-DISPONIBILIDAD"},
     *      summary="REHACER IMPORTACIÓN AFILIADOS DISPONIBILIDAD",
     *      operationId="rollback_import_affiliats_availability",
     *      description="Para rehacer la importación de afiliados disponibilidad",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_import", type="string",description="fecha de importación required",example= "1999-01-01")
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
    public function rollback_import_affiliates_availability(Request $request) {
        $request->validate([
            'date_import' => 'required|date_format:"Y-m-d"',
        ]);
        DB::beginTransaction();
        try {
            $result['delete_step_1'] = false;
            $valid_rollback = false;
            $date_import = Carbon::parse($request->date_import);
            $year = (int)$date_import->format("Y");
            $month = (int)$date_import->format("m");
            $message = "Nada que eliminar";

            if($this->exists_data_import_affiliate_availability($month, $year)) {
                $result['delete_step_1'] = $this->delete_import_affiliate_availability($month, $year);

                if($result['delete_step_1'] == true) {
                    $valid_rollback = true;
                    $message = "Realizado con éxito";
                }
            }
            DB::commit();
            return response()->json([
                'message' => $message,
                'payload' => [
                    'valid_rollback' => $valid_rollback,
                    'delete_step' => $result
                ]
            ]);
        } catch(\Exception $e) {
            logger($e);
            return response()->json([
                'message' => "Hubo un error",
                'payload' => [
                    'error' => true,
                ]
            ], 500);
        }
    }

    public function exists_data_import_affiliate_availability($month, $year) {
        $exists_data = true;
        $verify_data = DB::connection('db_aux')->select("SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year");

        if($verify_data[0]->count == 0) $exists_data = false;

        return $exists_data;
    }

    public function delete_import_affiliate_availability($month, $year) {
        if($this->exists_data_import_affiliate_availability($month,$year))
        {
            $query = "DELETE FROM copy_affiliates_availability WHERE a_o = $year AND mes = $month";
            $query = DB::connection('db_aux')->select($query);
            DB::commit();
            return true;
        } else
            return false;
    }

    /**
     * @OA\Post(
     *      path="/api/affiliate/import_affiliates_availability_progress_bar",
     *      tags={"IMPORTACION-AFILIADOS-DISPONIBILIDAD"},
     *      summary="INFORMACIÓN DE PROGRESO DE IMPORTACIÓN AFILIADOS DISPONIBILIDAD",
     *      operationId="import_affiliates_availability_progress_bar",
     *      description="Muestra la información de la importación de afiliados en disponibilidad  (-1)Si existió algún error en algún paso, (100) Si todo fue exitoso, (25 50 75)paso 1,2,3 respectivamente (0)si esta iniciando la importación",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *             @OA\Property(property="date_import", type="string",description="fecha de importacion required",example= "2023-01-01")
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
    public function import_affiliates_availability_progress_bar(Request $request) {
        $request->validate([
            'date_import' => 'required|date_format:"Y-m-d"',
        ]);

        $date_import = Carbon::parse($request->date_import);
        $year = (int)$date_import->format("Y");
        $month = (int)$date_import->format("m");
        $message = "Exito";

        $result['file_exists'] = false;
        $result['file_name'] = "";
        $result['percentage'] = 0;
        $result['query_step_1'] = false;
        $result['query_step_2'] = false;

        $task['task_step_1'] = false;
        $task['task_step_2'] = false;

        // $verify = DB::connection('db_aux')->select("SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND affiliate_id IS  NULL AND state NOT LIKE 'accomplished'")[0]->count;
        $task['task_step_1'] = $this->exists_data_import_affiliate_availability($month, $year);
        // logger($verify);

        //****** paso 2 *****/
        $verify = DB::connection('db_aux')->select("SELECT count(id) FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND state like 'unrealized'")[0]->count;

        // $affiliates_in_file = "SELECT affiliate_id FROM copy_affiliates_availability WHERE mes = $month AND a_o = $year AND STATE LIKE 'accomplished' AND error_mensaje <> 'NO ACTUALIZADO'";
        // $affiliates_in_file = DB::connection('db_aux')->select($affiliates_in_file);
        // $affiliates_in_file = collect($affiliates_in_file);
        // $amount_in_file = $affiliates_in_file->count();

        // como determinamos si actualizo a los afiliados
        // $affiliates = Affiliate::all()->pluck('id');
        // $amount = $affiliates->whereIn('id', $affiliates_in_file)->where('affiliate_state_id', '=', 3)->count();

        $task['task_step_2'] = $this->exists_data_import_affiliate_availability($month, $year) && $verify == 0 ? true : false;

        //verificamos si existe el archivo de importación
        $date_month= strlen($month) == 1 ? '0'.$month : $month;
        $new_file_name = "disponibilidad-".$date_month."-".$year.'.csv';
        $base_path = 'afiliados/afiliados_disponibilidad/'.$date_month.'-'.$year.'/'.$new_file_name;
        if (Storage::disk('ftp')->has($base_path)) {
            $result['file_name'] = $new_file_name;
            $result['file_exists'] = true;
        }

        if($result['file_exists'] == true && $task['task_step_1'] == true && $task['task_step_2']  == true ){
            $result['percentage'] = 100;
            $result['query_step_1'] = true;
            $result['query_step_2'] = true;
        } elseif ($result['file_exists'] == true && $task['task_step_1'] == true && $task['task_step_2']  == false ){
            $result['percentage'] = 50;
            $result['query_step_1'] = true;
        } else {
            $result['percentage'] = -1;
            $message = "Error! Algo salió mal en algún paso.";
        }

        return response()->json([
            'message' => $message,
            'payload' => [
                'import_progress_bar' =>  $result,
                'data_count' => $this->data_count($month,$year),
                'task'=> $task
            ],
        ]);
    }
}
