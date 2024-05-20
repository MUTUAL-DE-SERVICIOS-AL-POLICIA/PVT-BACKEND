<?php

namespace App\Http\Controllers\EconomicComplement;

use App\Http\Controllers\Controller;
use App\Models\Devolution;
use App\Models\Due;
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

}
