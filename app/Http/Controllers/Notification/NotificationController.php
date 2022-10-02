<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\NotificationRequest;
use App\Models\EconomicComplement\EcoComProcedure;
use App\Models\EconomicComplement\EcoComState;
use App\Models\EconomicComplement\EcoComStateType;
use App\Models\EconomicComplement\EconomicComplement;
use App\Models\EconomicComplement\EcoComModality;
use App\Models\Notification\NotificationSend;
use App\Models\Affiliate\Hierarchy;
use App\Models\ObservationType;
use App\Models\Admin\Module;
use App\Models\Affiliate\AffiliateToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ExceptionMicroservice;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Affiliate\Affiliate;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notification/get_semesters",
     *     tags={"NOTIFICACIONES"},
     *     summary="LISTADO DE SEMESTRES",
     *     operationId="getSemestres",
     *     description="Obtiene el listado de semestres para complemento económico",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get list of cities
     *
     * @param Request $request
     * @return void
     */
    public function get_semesters(){
        $semesters = EcoComProcedure::select(['id', 'year', 'semester'])
        ->orderBy('year')
        ->get();
        $results = [];
        foreach($semesters as $semester) {
            array_push($results, (object)['id' => $semester->id, 'period' => explode("-", $semester->year)[0]." - ".$semester->semester]);
        }
        
        return response()->json([
            'semesters' => $results,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notification/get_observations/{module_id}",
     *     tags={"NOTIFICACIONES"},
     *     summary="LISTADO DE OBSERVACIONES",
     *     operationId="getObservaciones",
     *     description="Obtiene el listado de las observaciones de tipo 'AT' para complemento económico",
     *     @OA\Parameter(
     *          name="module_id",
     *          in="path",
     *          description="",
     *          required=true,
     *          @OA\Schema(
     *              type="integer",
     *              format="int64"        
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get list of cities
     *
     * @param Request $request
     * @return void
     */
    public function get_observations($module_id){
        $observation_types = Module::find($module_id)->observation_types;
        return response()->json([
            'observations' => $observation_types
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notification/get_modalities_payment/{state_type_id}",
     *     tags={"NOTIFICACIONES"},
     *     summary="LISTADO DE MODALIDADES DE PAGO",
     *     operationId="getModalidadesDePago",
     *     description="Obtiene el listado de las modalidades de pago para complemento económico",
     *     @OA\Parameter(
     *         name="state_type_id",
     *         in="path",
     *         description="Pagado, habilitado y en proceso",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get list of cities
     *
     * @param Request $request
     * @return void
     */
    public function get_modalities_payment($eco_com_state_type_id) {
        $modalities_payment = EcoComStateType::find($eco_com_state_type_id)->eco_com_state;
        return response()->json([
            'modalities_payment' => $modalities_payment
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notification/get_beneficiary_type",
     *     tags={"NOTIFICACIONES"},
     *     summary="LISTADO DE TIPOS DE BENEFICIARIOS",
     *     operationId="getTiposBeneficiarios",
     *     description="Obtiene el listado de los tipos de beneficiarios para complemento económico",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get list of cities
     *
     * @param Request $request
     * @return void
     */
    public function get_beneficiary_type(){
        $types = EcoComModality::join('procedure_modalities', 'eco_com_modalities.procedure_modality_id', '=', 'procedure_modalities.id')
                                ->select('procedure_modalities.id', 'procedure_modalities.name')
                                ->distinct()
                                ->get();
        return response()->json([
            'beneficiary_type' => $types
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notification/get_hierarchical_level",
     *     tags={"NOTIFICACIONES"},
     *     summary="LISTADO DE JERARQUIAS",
     *     operationId="getJerarquias",
     *     description="Obtiene el listado de las jerarquias para complemento económico",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get list of cities
     *
     * @param Request $request
     * @return void
     */
    public function get_hierarchical_level(){
        $hierarchies = Hierarchy::select('id', 'name')->where('id', '>', 1)
        ->orderBy('id')
        ->get();
        return response()->json([
            'hierarchies' => $hierarchies
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notification/get_actions",
     *     tags={"NOTIFICACIONES"},
     *     summary="LISTADO DE ACCIONES",
     *     operationId="getAcciones",
     *     description="Obtiene el listado de las acciones permitidas",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get list of cities
     *
     * @param Request $request
     * @return void
     */
    public function get_actions() {
        return response()->json([
            'Acciones' => [
                [
                    'id' => 1,
                    'shortened' => 'Recepción de requisitos',
                    'name' => 'Recepción de requisitos para el pago del complemento económico'
                ],
                [
                    'id' => 2,
                    'shortened' => 'Pago complemento económico',
                    'name' => 'Pago del complemento económico perteneciente al semestre actual'
                ],
                [
                    'id' => 3,
                    'shortened' => 'Observaciones de trámites',
                    'name' => 'Observaciones de trámites de complemento económico'
                ]
            ]
        ]);
    }

    // Microservicio para consumir la ruta del backend node
    public function delegate_shipping($data, $tokens, $ids){ 
        $res = [];
        try{
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->acceptJson()
                ->post(env('URL_BACKEND_NODE'),[
                    'tokens' => $tokens, 
                    'title'  => 'COMPLEMENTO ECONÓMICO',
                    'body'   => 'COMUNICADO',
                    'image'  => env('NOTIFICATION_IMAGE', ''),
                    'data'   => $data
                ]);
            if($response->successful()) {
                $delivered = [];                    // Para el estado en la base de datos
                $message   = $response['message'];
                $responses = $message['responses'];

                $i = 0;
                $delivered = array();
                foreach($responses as $check) {
                    $var = $check['success'] ? true : false;
                    $aux = array();
                    $aux = array(
                        'affiliate_id' => $ids[$i],
                        'status' => $var
                    );
                    array_push($delivered, $aux);
                    $i++;
                }
                $res['status']       = true;
                $res['delivered']    = $delivered;
                $res['successCount'] = $message['successCount'];
                $res['failureCount'] = $message['failureCount'];
                $res['message'] = 'Notificación masiva exitosa';
            } else {
                if(count($tokens) == 0) {
                    $res['status']  = true;
                    $res['message'] = "Nada que enviar!";
                } else {
                    $res['status']  = false;
                    $res['message'] = $response['message']['message'];
                }
            }
        }
        catch(\Exception $e) {
            $res['status'] = false;
            $res['message'] = $e->getMessage();
        }
        return $res;
    }

    // Guardar en el registro tabla notification_send
    public function to_register($user_id, $delivered, $message, $subject, $ids) {
        $eco_com = new EconomicComplement();
        $alias = $eco_com->getMorphClass();
        $i = 0;
        foreach($ids as $id) {
            $obj = (object)$message;
            $notification_send = new NotificationSend();
            $notification_send->create([
                'user_id' => $user_id,
                'carrier_id' => 1,
                'number_id' => null, 
                'sendable_type' => $alias,
                'sendable_id' => $id,
                'send_date' => Carbon::now(),
                'delivered' => $delivered[$i]['status'],
                'message' => json_encode(['data' => $obj]),
                'subject' => $subject
            ]);
            $i++;
        }
    }

    // Función que crea las tablas temporales necesarias para el manejo de las consultas
    public function create_temporary_tables_payments() {
        DB::transaction(function() {
            DB::statement('drop table if exists tmp_affiliates');
            DB::statement("create temp table tmp_affiliates (
                affiliate_id integer,
                firebase_token varchar,
                economic_complement_id integer,
                last_year date,
                last_semester varchar,
                payment_id integer,
                payment_name varchar,
                modality_id integer,
                modality_name varchar)");
            DB::statement('drop table if exists temporal');
            DB::statement('create temp table temporal as (
                select ts.affiliate_id as affiliate_id, ts.firebase_token as firebase_token, ec.id as economic_complement_id, ecp.year as last_year, ecp.semester as last_semester, ecs.id as payment_id, ecs.name as payment_name, pm.id as modality_id, pm.name as modality_name
                from affiliate_tokens ts
                inner join economic_complements ec
                on ts.affiliate_id = ec.affiliate_id
                inner join eco_com_procedures ecp
                on ec.eco_com_procedure_id = ecp.id
                inner join eco_com_states ecs
                on ec.eco_com_state_id = ecs.id
                inner join eco_com_modalities ecm
                on ec.eco_com_modality_id = ecm.id
                inner join procedure_modalities pm
                on ecm.procedure_modality_id = pm.id
                where ts.api_token is not null
                --and ts.firebase_token is not null
                and ecs.id in (24, 25, 29)
                order by ts.affiliate_id, ecp.year desc)');
            DB::statement('create or replace procedure fill_temp()
                        as
                        $$
                        declare
                                campo RECORD;
                            begin 
                                for campo in (select affiliate_id
                                            from affiliate_tokens
                                            where api_token is not null
                                            order by affiliate_id)
                                loop
                                    insert into tmp_affiliates (affiliate_id, firebase_token, economic_complement_id, last_year, last_semester, payment_id, payment_name, modality_id, modality_name)
                                    select ts.affiliate_id, ts.firebase_token, ts.economic_complement_id, ts.last_year, ts.last_semester, ts.payment_id, ts.payment_name, ts.modality_id, ts.modality_name
                                    from temporal ts
                                    where ts.affiliate_id = campo.affiliate_id
                                    offset 0 rows
                                    fetch first 1 row only;
                                end loop;
                            end;
                            $$ LANGUAGE plpgsql;');
            DB::statement('call fill_temp();');
        });
    }

    // Función que crea la tabla temporal necesaria para las observaciones
    public function create_temporary_table_observation($year, $semester) {
        DB::transaction(function() use ($year, $semester) {
            DB::statement('drop table if exists tmp_observations');
            DB::statement("create temp table tmp_observations as (
                select distinct at.affiliate_id, at.firebase_token, ecp.semester, ecp.year
                from affiliate_tokens at
                inner join affiliate_devices ad
                on at.id = ad.affiliate_token_id
                inner join eco_com_procedures ecp
                on ad.eco_com_procedure_id = ecp.id
                where ecp.year = '$year'
                and ecp.semester = '$semester'
                and at.api_token is not null
                --and at.firebase_token is not null
            )");
        });
    }

    // Para la tabla 
    public function shippable_list($query, $filters) {
        $base = collect(DB::select($query));
        $result = $base->filter(function ($value, $key) use ($filters) {
            return str_contains($value->last_name, $filters[0])
                && str_contains($value->mothers_last_name, $filters[1])
                && str_contains($value->first_name, $filters[2])
                && str_contains($value->second_name, $filters[3])
                && str_contains($value->identity_card, $filters[4]);
        });
        $response = array();
        foreach($result as $key => $value) {
            $value->send = false;
            array_push($response, $value);
        }
        return response()->json([
            'error' => false,
            'message' => 'Listado de personas a notificar',
            'data' => $response
        ]);
    }

    // Proceso de consulta 
    public function consultation_process($count, $sql_base, $params){
        $result = false;
        $offset = 0;
        $sql_base .= " \n"; 
        $query = $sql_base . "limit 500 offset $offset";
        $i = 0;
        $res = [];
        do {
            $i++;
            $chunk = DB::select($query);
            $offset += 500;
            foreach($chunk as $crumb){
                array_push($params['tokens'], $crumb->firebase_token);
                array_push($params['ids'],    $crumb->affiliate_id);
            }
            $res = $this->delegate_shipping($params['data'], $params['tokens'], $params['ids']); 
            if($res['status'] && count($params['tokens']) != 0) {
                $status = $res['delivered'];
                $this->to_register($params['user_id'], $status, $params['data'], $params['subject'], $params['ids']);
                $result = true;
            }
            else { $result = false; break; }
            logger("-----------------    ENVÍO LOTE NRO $i  --------------------------");
            sleep(1);
        } while($i < $count);
        return $result ? response()->json([
            'error'   => false,
            'message' => $res['message'],
            'data'    => [
                'delivered'     => $res['delivered'],
                'success_count' => $res['successCount'],
                'failure_count' => $res['failureCount']
            ]
        ]) : response()->json([
            'error'   => true,
            'message' => $res['message'],
            'data'    => []
        ], 404);
    }

    /**
     * @OA\Post(
     *     path="/api/notification/list_to_notify",
     *     tags={"NOTIFICACIONES"},
     *     summary="LISTADO DE BENEFICIARIOS A NOTIFICAR",
     *     operationId="ListToNotify",
     *     description="Listado de beneficiarios para enviar notificación",
     *     @OA\RequestBody(
     *          description= "Listado de notificaciones",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="action", type="string",description="Recepción de requerimientos, complemento económico u observaciones (Receipt_of_requirements, economic_complement_payment, observatinos", example="economic_complement_payment"),
     *              @OA\Property(property="payment_method", type="integer",description="Método de pago para el complemento económico (Abono en cuenta SIGEP, Ventanilla Banco Unión y a domicilio)",example="24"),
     *              @OA\Property(property="modality", type="integer",description="Modalidad (vejez, viudedad u orfandad)", example="29"),
     *              @OA\Property(property="type_observation", type="integer",description="Tipo de observación", example="2"),
     *              @OA\Property(property="year", type="string",description="Fecha perteneciente del complemento económico",example="2022-01-01"),
     *              @OA\Property(property="semester", type="string",description="Semestre del complemento económico observado",example="Segundo"),
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     *  mass_notification 
     *
     * @param Request $request
     * @return void
     */
    public function list_to_notify(Request $request) {

        $validator = Validator::make($request->all(), [
            'action' => [
                'required',
                'numeric',
                function($attribute, $value, $fail) {
                    if(!in_array($value, [1,2,3]))
                    $fail('El valor del campo '.$attribute.' es incorrecto');
                }
            ],
            'payment_method' => [
                'exclude_if:action,1,3',
                'required_if:action,2',
                'required',
                'numeric',
                function($attribute, $value, $fail) {
                    if(!in_array($value, [0,24,25,29])) 
                    $fail('El '.$attribute.' (método de pago) es incorrecto');
                }
            ],
            'modality' => [
                'exclude_if:action3,1',
                'exclude_if:payment_method,0',
                'numeric',
                function($attribute, $value, $fail) {
                    if(!in_array($value, [29, 30, 31]))
                    $fail('El '.$attribute. ' (modalidad) es incorrecto');
                }
            ],
            'type_observation' => 'required_if:action,3|numeric',
            'hierarchies' => 'numeric',
            'year' => 'required_if:action,3',
            'semester' => 'required_if:action,3|string'
        ]);

        if($validator->fails()) {
            $keys = $validator->errors()->keys();
            $errors = [];
            foreach($keys as $key) {
                $errors[$key] = $validator->errors()->get($key);
            }
            return response()->json([
                'error' => true,
                'errors' => $errors
            ], 422);
        }

        try {
            $action = $request->action;
            if($action === 1) { // recepción de requisitos
                $query = "select at2.affiliate_id, eca.last_name, eca.mothers_last_name, eca.first_name, eca.second_name, eca.identity_card
                        from affiliate_tokens at2
                        inner join economic_complements ec
                        on at2.affiliate_id = ec.affiliate_id
                        inner join eco_com_applicants eca
                        on ec.id = eca.economic_complement_id
                        inner join eco_com_procedures ecp
                        on ec.eco_com_procedure_id = ecp.id
                        where ec.eco_com_procedure_id in (
                                select ecp.id
                                from eco_com_procedures ecp
                                order by ecp.id desc
                                offset 0 rows
                                fetch first 1 row only
                        )
                        --and at2.api_token is not null
                        --and at2.firebase_token is not null";
            } else {
                if($action === 2) { 
                    $payment_method = $request->payment_method;
                    $this->create_temporary_tables_payments(); 
                    if($payment_method == 0) { // A todos los habilitados para pago de complemento económico
                        logger("A todos los habilitados para pago de complemento económico");
                        $query = "select ta.affiliate_id, eca.last_name, eca.mothers_last_name, eca.first_name, eca.second_name, eca.identity_card
                                from tmp_affiliates ta
                                inner join eco_com_applicants eca
                                on ta.economic_complement_id = eca.economic_complement_id
                                order by ta.affiliate_id";

                    } else { // Cualquier método de pago
                        if($request->has('modality')){
                            $modality = $request->modality;
                            if($request->has('hierarchies')){
                                $hierarchies = $request->hierarchies;
                                logger("Al método de pago $payment_method con su modalidad de $modality y con la jerarquia $hierarchies");
                                $query = "select ta.affiliate_id, eca.last_name, eca.mothers_last_name, eca.first_name, eca.second_name, eca.identity_card
                                        from tmp_affiliates ta
                                        left join eco_com_applicants eca
                                        on ta.economic_complement_id = eca.economic_complement_id
                                        inner join affiliates a
                                        on ta.affiliate_id = a.id
                                        inner join degrees d
                                        on a.degree_id = d.id
                                        inner join hierarchies h
                                        on d.hierarchy_id = h.id
                                        where payment_id = $payment_method
                                        and ta.modality_id = $modality
                                        and h.id = $hierarchies";
                            } else {
                                logger("Al método de pago $payment_method con su modalidad de $modality");
                                $query = "select distinct ta.affiliate_id, eca.last_name, eca.mothers_last_name, eca.first_name, eca.second_name, eca.identity_card
                                        from tmp_affiliates ta
                                        inner join eco_com_applicants eca
                                        on ta.economic_complement_id = eca.economic_complement_id
                                        where payment_id = $payment_method
                                        and modality_id = $modality";
                            }
                        } else {
                            logger("Solo a su método de pago $payment_method");
                            $query = "select ta.affiliate_id, eca.last_name, eca.mothers_last_name, eca.first_name, eca.second_name, eca.identity_card
                                    from tmp_affiliates ta
                                    inner join eco_com_applicants eca
                                    on ta.economic_complement_id = eca.economic_complement_id
                                    where payment_id = $payment_method";
                        }
                    }
                } elseif($action === 3) {
                    $year = $request->year;
                    $semester = $request->semester;
                    $this->create_temporary_table_observation($year, $semester); 
                    $type = $request->type_observation;
                    logger("observación del $year año, con $semester semestre y tipo de observación $type");
                    $query = "select distinct tos.affiliate_id, eca.last_name, eca.mothers_last_name, eca.first_name, eca.second_name, eca.identity_card
                                from tmp_observations tos, economic_complements ec, observables o, observation_types ot, eco_com_applicants eca
                                where tos.affiliate_id = ec.affiliate_id
                                and o.observable_type = 'economic_complements'
                                and o.observable_id = ec.id
                                and ec.id = eca.economic_complement_id
                                and o.observation_type_id = $type
                                and o.enabled = true
                                and ot.type = 'AT'
                                and ot.description is not null
                                and ot.description <> ''";
                }
            }
            $filters = array();
            $filters[0] = $request->last_name ?? "";
            $filters[1] = $request->mothers_last_name ?? "";
            $filters[2] = $request->first_name ?? "";
            $filters[3] = $request->second_name ?? "";
            $filters[4] = $request->identity_card ?? "";
            return $this->shippable_list($query, $filters);
        } catch(\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/notification/send_mass_notification",
     *     tags={"NOTIFICACIONES"},
     *     summary="ENVÍO DE NOTIFICACIONES MASIVAS",
     *     operationId="sendMassNotification",
     *     description="Envío de notificaciones masivas",
     *     @OA\RequestBody(
     *          description= "Envío de notificaciones masivas",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="title", type="string",description="Título de la notificación", example="Comunicado Complemento económico"),
     *              @OA\Property(property="message", type="string",description="Mensaje de la notificación",example="Señor affiliado {{nombre}} se apertura la recepción de requisitos para el trámite de pago de complemento económico"),
     *              @OA\Property(property="sends", type="object",description="Array de personas a notificar (beneficiarios)", example="[{'affiliate_id': 5964, 'send': true}]"),
     *              @OA\Property(property="image", type="string",description="Url de la imagen como cuerpo de la notificación", example="http://google.com/image"),
     *              @OA\Property(property="attached", type="string",description="Adjunto del mensaje",example="Comunicado"),
     *              @OA\Property(property="user_id", type="integer",description="Id del usuario que envía la notificación",example="1"),
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     *  mass_notification 
     *
     * @param Request $request
     * @return void
     */
    public function send_mass_notification($request)
    {
        $validator = Validator::make($request, [
            'title' => 'required|string',
            'message' => 'required|string',
            'sends' => 'required|array|min:0',
        ]);

        if($validator->fails()) {
            $keys = $validator->errors()->keys();
            $errors = [];
            foreach($keys as $key) {
                $errors[$key] = $validator->errors()->get($key);
            }
            return response()->json([
                'error' => true,
                'errors' => $errors
            ], 422);
        }

        try {
            ini_set('max_execution_time', 5); 
            $title_notification = $request['title'];
            $message            = $request['message'];
            $image              = $request->image ?? "";
            $sends              = $request['sends'];
            $amount             = count($sends);
            $publication_date   = Carbon::now()->format('Y-m-d');
            $data = [
                'title' => $title_notification,
                'image' => $image,
                'PublicationDate' => $publication_date,
                'text' => $message
            ];
            $params = [];
            $tokens = [];
            $ids    = [];
            $params['data']    = $data;
            $params['tokens']  = $tokens;
            $params['ids']     = $ids;
            $params['subject'] = $request['attached'];
            $params['user_id'] = $request['user_id'];

            $interval = ceil(($amount * 1.0)/ 500);
            $i = 0;
            do {
                $i++;
                foreach($sends[0] as $send) {
                    if($send['send']) {
                        $firebase_token = AffiliateToken::whereAffiliateId($send['affiliate_id'])->select('firebase_token')->get()[0];
                        array_push($params['tokens'], $firebase_token['firebase_token']);
                        array_push($params['ids'],   $send['affiliate_id']);
                    }
                }
                $res = $this->delegate_shipping($params['data'], $params['tokens'], $params['ids']); 
                if($res['status'] && count($params['tokens']) != 0) {
                    $status = $res['delivered'];
                    $this->to_register($params['user_id'], $status, $params['data'], $params['subject'], $params['ids']);
                    $result = true;
                }
                else { $result = false; break; }
                logger("-----------------    ENVÍO LOTE NRO $i  --------------------------");
                sleep(1);
            } while($i < $interval);

            return $result ? response()->json([
                'error'   => false,
                'message' => $res['message'],
                'data'    => [
                    'delivered'  => $res['delivered'],
                    'success_count' => $res['successCount'],
                    'failure_count' => $res['failureCount']
                ]
            ]) : response()->json([
                'error'   => true,
                'message' => $res['message'],
                'data'    => []
            ], 404);
        } catch(\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function send_notifications(Request $request)
    {
        /*try
        {
            $request->validate([
                'title' => 'required|string',
                'message' => 'required|string',
                'file' => 'required|file|mimes:xlsx'
            ]);*/
            
            $rows = Excel::toArray([], $request->file);
            $rows = $rows[0];
            $affiliates = collect();
            $send = collect();
            foreach($rows as $row)
            {
                if($row[0] != null)
                {
                    if(Affiliate::find($row[0]) != null && Affiliate::find($row[0])->affiliate_token != null && Affiliate::find($row[0])->affiliate_token->firebase_token != null)
                    {
                        $affiliates->push([
                            'affiliate_id' => $row[0],
                            'send' => true
                        ]);
                    }
                }
            }
            $send->push([
                'title' => $request->title,
                'message' => $request->message,
                'attached' => $request->attached,
                'user_id' => 1,
                'sends' => array($affiliates)
            ]);
            $send = $send[0];
            return $this->send_mass_notification($send);
        /*} catch(\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }*/
    }
}
