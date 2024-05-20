<?php

namespace App\Http\Controllers\EconomicComplement;

use App\Http\Controllers\Controller;
use App\Models\Devolution;
use App\Models\Due;
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
        $movements= EcoComMovement::where("affiliate_id",$affiliate_id)->get();
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
            $movement_objects->push($movement_object);
        }
        return response()->json([
            'error' => "false",
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
            $last_movement = EcoComMovement::where("affiliate_id", $request->affiliate_id)->latest()->first();
            $previous_balance = $last_movement->balance;
            $eco_com_movement->balance = $previous_balance+$totalMount;
        }else{
            $eco_com_movement->balance=$totalMount;
        }
        $eco_com_movement->save();
        return response()->json([
            'error' => "false",
            'message' => 'Listado de movimientos de dinero de pagos en demasia',
            'payload' => [
                'movements' => $eco_com_movement
            ]]);
    }
    public function storeDirectPayment(Request $request)
    {
        $exist_movement = EcoComMovement::where('affiliate_id', $request->affiliate_id)->exists();
        if ($exist_movement) {
            $last_movement = EcoComMovement::where('affiliate_id', $request->affiliate_id)->latest()->first();
            if ($last_movement->balance > 0) {
                $direct_payment = new EcoComDirectPayment();
                $direct_payment->amount = $request->amount;
                $direct_payment->voucher = $request->voucher;
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
}
