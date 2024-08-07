<?php

namespace App\Http\Controllers\EconomicComplement;

use App\Http\Controllers\Controller;
use App\Models\Devolution;
use App\Models\Due;
use App\Models\EconomicComplement\DiscountTypeEconomicComplement;
use App\Models\EconomicComplement\EcoComDirectPayment;
use App\Models\EconomicComplement\EcoComMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\Cast\Object_;

class EcoComMovementController extends Controller
{
    public function index(Request $request,$affiliate_id)
    {
        $request['affiliate_id']=$affiliate_id;
        $request->validate([
            'affiliate_id' => 'required|int',
        ]);
        $movements= EcoComMovement::where("affiliate_id",$affiliate_id)->orderby('id')->get();
        $movement_objects= collect();
        $correlative=1;
        foreach ($movements as $movement)
        {
            $movement_object = new \stdClass();
            $movement_object->correlative=$correlative++;
            $movement_object->id=$movement->id;
            $movement_object->affilate_id=$movement->affiliate_id;
            $movement_object->description=$movement->description;
            $movement_object->amount=$movement->amount;
            $movement_object->balance=$movement->balance;
            $movement_object->created_at = Carbon::parse($movement->created_at)->format('d-m-Y');
            if ($movement->movement_type == "devolutions") {
                $movement_object->has_payment_commitment = Devolution::find($movement->movement_id)->has_payment_commitment;
            }
            $movement_objects->push($movement_object);
        }
        return response()->json([
            'error' => false,
            'message' => 'Listado de movimientos de dinero de pagos en demasia',
            'payload' => [
                'movements' => $movement_objects
            ]
            ]);
    }
    public function storeDevolution(Request $request)
    {
        $request->validate([
            'affiliate_id' => 'required|int'
        ]);
        $devolution=new Devolution();
        $devolution->affiliate_id=$request->affiliate_id;
        $devolution->observation_type_id=13;
        $dues=$request->dues;
        $totalMount = 0;
        foreach ($dues as $due) {
           $totalMount += $due['amount'];
        }
        $devolution->total=$totalMount;
        $devolution->save();
        foreach ($dues as $due) {
            $due_object=new Due();
            $due_object->devolution_id = $devolution->id;
            $due_object->eco_com_procedure_id = $due['eco_com_procedure_id'] ;
            $due_object->amount=$due['amount'];
            $due_object->save();
        }
        $eco_com_movement=new EcoComMovement();
        $eco_com_movement->affiliate_id=$request->affiliate_id;
        $eco_com_movement->movement_id=$devolution->id;
        $eco_com_movement->movement_type=$devolution->getTable();
        $eco_com_movement->description = "DEUDA";
        $eco_com_movement->amount = $totalMount;
        $exists_last_movement=EcoComMovement::where("affiliate_id",$request->affiliate_id)->exists();
        if ($exists_last_movement) {
            $last_movement = EcoComMovement::where("affiliate_id", $request->affiliate_id)->latest()->orderBy('id', 'desc')->first();
            $previous_balance = $last_movement->balance;
            $eco_com_movement->balance = $previous_balance+$totalMount;
        }else{
            $eco_com_movement->balance=$totalMount;
        }
        $eco_com_movement->save();
        return response()->json([
            'error' => false,
            'message' => 'Listado de movimientos de dinero de pagos en demasia',
            'payload' => [
                'movements' => $eco_com_movement
            ]]);
    }
    public function storeDirectPayment(Request $request)
    {
        $exist_movement = EcoComMovement::where('affiliate_id', $request->affiliate_id)->exists();
        if ($exist_movement) {
            $last_movement = EcoComMovement::where('affiliate_id', $request->affiliate_id)->latest()->orderBy('id', 'desc')->first();
            if ($last_movement->balance > 0) {
                $direct_payment = new EcoComDirectPayment();
                $direct_payment->amount = $request->amount;
                $direct_payment->voucher = $request->voucher;
                $direct_payment->payment_date = $request->payment_date;
                if ($direct_payment->amount <= $last_movement->balance) {
                    $direct_payment->save();
                    $eco_com_movement = new EcoComMovement();
                    $eco_com_movement->affiliate_id = $request->affiliate_id;
                    $eco_com_movement->movement_id = $direct_payment->id;
                    $eco_com_movement->movement_type = $direct_payment->getTable();
                    $eco_com_movement->description = 'PAGO DIRECTO';
                    $eco_com_movement->amount = $direct_payment->amount;
                    $eco_com_movement->balance = $last_movement->balance - $direct_payment->amount;
                    $eco_com_movement->save();
                    return response()->json([
                        'error' => false,
                        'message' => 'Pago registrado exitosamente',
                        'payload' => [
                            'movement' => $eco_com_movement
                        ]
                    ]);
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'No se puede registrar el pago, el monto registrado es mayor a la deuda',
                        'payload' => []
                    ]);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'No se puede registrar el pago, la deuda es 0',
                    'payload' => []
                ]);
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => 'No se encontraron movimientos anteriores',
                'payload' => []
            ]);
        }
    }
    public function softDeleteMovement(Request $request, $affiliate_id){
        $request['affiliate_id']=$affiliate_id;
        $request->validate([
            'affiliate_id' => 'required|int'
        ]);
        $movement = EcoComMovement::where('affiliate_id', $request->affiliate_id)->latest()->first();
        if ($movement) {
            $movement_type=$movement->movement_type;
            switch ($movement_type) {
                case 'devolutions':
                    $devolution=Devolution::find($movement->movement_id);
                    $dues=Due::where("devolution_id",$devolution->id)->get();
                    foreach ($dues as $due ) {
                        $due->delete();
                    }
                    $devolution->delete();
                    break;
                case 'eco_com_direct_payments':
                    $direct_payment=EcoComDirectPayment::find($movement->movement_id);
                    $direct_payment->delete();
                    break;
                case 'discount_type_economic_complement':
                    return response()->json([
                        'error' => false,
                        'message' => 'No se puede eliminar el movimiento debido a que ya se pago el complemento',
                        'payload' => []
                    ]);
                    break;
                default:
                    break;
            }
            $movement->delete();
            return response()->json([
                'error' => false,
                'message' => 'Movimiento eliminado',
                'payload' => [
                    "movement"=>$movement
                ]
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Movimiento no encontrado',
                'payload' => []
            ]);
        }
    }
    public function show_details(Request $request, $movement_id)
    {
        $request['movement_id'] = $movement_id;
        $request->validate([
            'movement_id' => 'required|int'
        ]);
        $eco_com_movement = EcoComMovement::find($movement_id);
        $detail_objects = collect();
        switch ($eco_com_movement->movement_type) {
            case 'devolutions':
                $devolution_id = $eco_com_movement->movement_id;
                $list_dues = Due::where("devolution_id", $devolution_id)->get();
                $correlative = 1;
                foreach ($list_dues as $due) {
                    $due_object = new \stdClass();
                    $due_object->correlative = $correlative++;
                    $due_object->name = $due->eco_com_procedure->semester . " SEMESTRE " . $due->eco_com_procedure->year;
                    $due_object->amount = $due->amount;
                    $detail_objects->push($due_object);
                }
                return response()->json([
                    'error' => false,
                    'message' => 'Detalle de deudas',
                    'payload' => [
                        'detail' => $detail_objects
                    ]
                ]);
                break;
            case 'eco_com_direct_payments':
                $eco_com_direct_payment = EcoComDirectPayment::find($eco_com_movement->movement_id);
                $eco_com_direct_payment_object = new \stdClass();
                $eco_com_direct_payment_object->voucher = $eco_com_direct_payment->voucher;
                $eco_com_direct_payment_object->payment_date = $eco_com_direct_payment->payment_date;
                $detail_objects->push($eco_com_direct_payment_object);
                return response()->json([
                    'error' => false,
                    'message' => 'Detalle de pagos directos',
                    'payload' => [
                        'detail' => $detail_objects
                    ]
                ]);
                break;
            case 'discount_type_economic_complement':
                $discount = DiscountTypeEconomicComplement::find($eco_com_movement->movement_id);
                $discount_object = new \stdClass();
                $procedure = $discount->economic_complement->eco_com_procedure;
                $discount_object->procedure = $procedure-> semester . " SEMESTRE ".$procedure->year;
                $detail_objects->push($discount_object);
                return response()->json([
                    'error' => false,
                    'message' => 'Detalle de pagos mediante trámite',
                    'payload' => [
                        'detail' => $detail_objects
                    ]
                ]);
                break;

            default:
                return response()->json([
                    'error' => true,
                    'message' => 'hubo un problema',
                    'payload' => [
                        'detail' => $detail_objects
                    ]
                ]);
                break;
        }
    }
    public function register_payment_commitement(Request $request, $movement_id){
        $request['movement_id'] = $movement_id;
        $eco_com_movement = EcoComMovement::find($request->movement_id);
        if ($eco_com_movement->movement_type == "devolutions") {
            $devolution=Devolution::find($eco_com_movement->movement_id);
            $devolution->percentage = $request->percentage;
            $devolution->start_eco_com_procedure_id = $request->start_eco_com_procedure_id;
            $devolution->has_payment_commitment=true;
            $devolution->save();
            return response()->json([
                'error' => false,
                'message' => 'se registro el compromiso de pago correctamente',
                'payload' => [
                    'devolution' => $devolution
                ]
            ]);
        }
        return response()->json([
            'error' => true,
            'message' => 'el movimiento no es una deuda'
        ]);
    }
}
