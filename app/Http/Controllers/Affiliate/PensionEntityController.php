<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate\PensionEntity;

class PensionEntityController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/affiliate/pension_entity",
     *      tags={"AFILIADO"},
     *      summary="LISTADO DE ENTIDADES A LAS QUE PERTENECE EL AFILIADO",
     *      operationId="getEntidades",
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
     * Get list of categories
     *
     * @param Request $request
     * @return void
     */

     public function index()
     {
        return PensionEntity::orderBy('name')->get();
     }

     public function show(PensionEntity $pension_entity)
     {
        return $pension_entity;
     }
}
