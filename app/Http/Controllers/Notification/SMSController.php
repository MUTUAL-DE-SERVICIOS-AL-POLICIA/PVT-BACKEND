<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Util;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SMSController extends Controller
{
    public function send_credentials(Request $request) {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            // 'shipments' => 'required|array|min:1',
            'shipments.*.id' => 'required|integer',
            'shipments.*.sms_num' => 'required|min:11|max:11',
            // 'shipments.*.sms_num' => 'required',
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

        $threshold = 10;
        if(Util::check_balance() <= $threshold) {
            return response()->json([
                'error' => true,
                'message' => 'Línea telefónica sin saldo',
                'cell_phone_number' => null,
                'errors' => []
            ]);
        }
        if(Util::delegate_shipping($shipments, $user_id, 1, 'affiliate')) {
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

    public function send_from_a_file(Request $request) {
        $user_id = $request->user_id;
        $rows = Excel::toArray([], $request->file);
        $rows = $rows[0];

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
        $threshold = 10;
        if(Util::check_balance() <= $threshold) {
            return response()->json([
                'error' => true,
                'message' => 'Línea telefónica sin saldo!',
                'data' => []
            ]);
        }
        return Util::delegate_shipping($shippable, $user_id) ? response()->json([
            'error' => false,
            'message' => 'Envío de mensajes exitoso!',
            'data' => [
                'delivereds' => $shippable,
                'fails' => $fails
                ]
            ]) : response()->json([
                'error' => true,
                'message' => 'Error en el envío',
                'data' => []
            ]);
    }

    public function check_balance() {
        return response()->json([
            'error' => false,
            'message' => 'Saldo disponible',
            'data' => [
                ['saldo' => Util::check_balance() . ' Bs']
            ]
        ]);
    }
}
