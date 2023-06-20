<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Util;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Jobs\ProcessNotification;
use App\Jobs\ProcessRegisterNotification;
use Illuminate\Support\Facades\Bus;

class SMSController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/notification/send_credentials",
     *     tags={"NOTIFICACIONES"},
     *     summary="ENVÍO DE CREDENCIALES",
     *     operationId="sendCredentials",
     *     description="Envío de credenciales para la oficina virtual a través de SMS",
     *     @OA\RequestBody(
     *          description= "Campos requeridos",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="user_id", type="integer",description="Id del usuario", example="1"),
     *              @OA\Property(property="shipments", type="array", example={{"id": 1, "sms_num": "(651)-48120", "message": "notificacion"}}, @OA\Items(@OA\Property(property="id", type="integer", example=""), @OA\Property(property="sms_num", type="string", example=""), @OA\Property(property="message", type="string", example=""))),
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
     *  send_crendentials
     *
     * @param Request $request
     * @return void
     */
    public function send_credentials(Request $request) {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'shipments.*.id' => 'required|integer',
            'shipments.*.sms_num' => 'required|min:11|max:11',
            'shipments.*.message' => 'required'
        ]);

        if($validator->fails()) {
            $keys = $validator->errors()->keys();
            $errors = [];
            foreach($keys as $key) {
                $errors[$key] = $validator->errors()->get($key);
            }
            return response()->json([
                'error' => true,
                'message' => 'Error de la validación',
                'cell_phone_number' => null,
                'errors' => $errors
            ], 422);
        }
        $shipments = $request->shipments;
        $user_id = $request->user_id;
        if(count($shipments) == 0) return response()->json([
            'error' => true,
            'message' => 'Nada que enviar!',
            'cell_phone_number' => null,
            'errors' => []
        ]);

        $notification_type = 6;
        if(Util::delegate_shipping($shipments, $user_id, 1, 'affiliate', $notification_type)) {
            return response()->json([
                'error' => false,
                'message' => 'Envío exitoso!',
                'cell_phone_number' => $request->shipments[0]['sms_num'],
                'errors' => []
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Envío fallido',
                'cell_phone_number' => null,
                'errors' => []
            ]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/notification/file",
     *     tags={"NOTIFICACIONES"},
     *     summary="ENVÍO DE SMS MASIVO MEDIANTE UN ARCHIVO",
     *     operationId="send_from_a_file",
     *     description="Envío de SMS masivo a través de un archivo",
     *     @OA\RequestBody(
     *          description= "Campos requeridos",
     *          required=true,
     *          @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *              @OA\Property(property="user_id", type="integer",description="Id del usuario", example="1"),
     *              @OA\Property(property="file", type="file", format="binary", description="Archivo que contiene el nup, número y mensaje"),
     *              )
     *          ),
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
     *  send_from_a_file
     *
     * @param Request $request
     * @return void
     */
    public function send_from_a_file(Request $request) {
        $user_id = $request->user_id;
        $rows = Excel::toArray([], $request->file);
        $rows = $rows[0];
        $notification_type = $request->action;

        $shippable = collect();
        $fails = collect();
        for($i = 0; $i < count($rows); $i++) {
            if($rows[$i][1] == null || explode('.', (log10($rows[$i][1]) + 1))[0] != 8 || $rows[$i][2] == null) {
                $message = $rows[$i][1] == null || $rows[$i][2] == null ? 'Registro nulo' : 'El número requiere 8 dígitos';
                $fails->push([
                    'número de registro' => $i + 1,
                    'mensaje de error' => $message
                ]);
                continue;
            }
            $shippable->push([
                'id' => $rows[$i][0],
                'sms_num' => $rows[$i][1],
                'message' => $rows[$i][2]
            ]);
        }
        $transmitter = 1;
        $morph = true;

        Bus::chain([
            new ProcessRegisterNotification($shippable, $user_id, $transmitter, $notification_type),
            new ProcessNotification($shippable, $user_id, $transmitter, $morph),
        ])->dispatch();

        return response()->json([
            'error' => false,
            'message' => 'Se inicio el proceso de notificación',
            'data' => []
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notification/balance/{telephone_line}",
     *     tags={"NOTIFICACIONES"},
     *     summary="SALDO SMS",
     *     operationId="balance",
     *     description="Obtiene el saldo de una línea telefonica",
     *     @OA\Parameter(
     *          name="telephone_line",
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
     * Get balance
     *
     * @param Request $request
     * @return void
     */
    public function check_balance_sms(Request $request) {
        $telephone_line = $request->telephone_line;
        return response()->json([
            'error' => false,
            'message' => 'Saldo disponible',
            'data' => [
                ['saldo' => Util::check_balance_sms() . ' Bs']
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notification/balance_ussd",
     *     tags={"NOTIFICACIONES"},
     *     summary="SALDO USSD",
     *     operationId="balance_ussd",
     *     description="Obtiene el saldo mediante ussd",
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
     * Get balance
     *
     * @param Request $request
     * @return void
     */
    public function check_balance_ussd(Request $request) {
        $result = Util::check_balance_ussd();
        return response()->json([
            'error' => false,
            'message' => 'Consulta de saldo',
            'data' => [
                'saldo' => $result
            ]
        ]);
    }
}
