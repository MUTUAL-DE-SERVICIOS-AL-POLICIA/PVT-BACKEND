<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate\Unit;

class UnitController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/affiliate/unit",
     *      tags={"AFILIADO"},
     *      summary="LISTADO DE UNIDADES DEL AFILIADO",
     *      operationId="getUnidades",
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *          type="object"
     *          )
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     * )
     *
     * Get list of Units
     *
     * @param Request $request
     * @return void
     */
    public function index()
    {
        return Unit::orderBy('name')->get();
    }

    public function show(Unit $unit)
    {
        return $unit;
    }
}
