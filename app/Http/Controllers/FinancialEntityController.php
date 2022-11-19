<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancialEntity;

class FinancialEntityController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/global/financial_entity",
     *     tags={"ENTIDAD FINANCIERA"},
     *     summary="LISTADO DE ENTIDADES FINANCIERAS",
     *     operationId="getEntidadesFinancieras",
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
    public function index()
    {
        return FinancialEntity::orderBy('name')->get();
    }

    public function show(FinancialEntity $financialEntity)
    {
        return $financialEntity;
    }
}
