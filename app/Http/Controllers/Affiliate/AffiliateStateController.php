<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate\AffiliateState;

class AffiliateStateController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/affiliate/affiliate_state",
     *      tags={"AFILIADO"},
     *      summary="LISTA DE ESTADOS DEL AFILIADO",
     *      operationId="getEstados",
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
        return AffiliateState::orderBy('name')->get();
    }

    public function show(AffiliateState $affiliate_state)
    {
        return $affiliate_state;
    }
}
