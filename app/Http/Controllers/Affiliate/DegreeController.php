<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate\Degree;

class DegreeController extends Controller
{
       /**
     * @OA\Get(
     *     path="/api/affiliate/degree",
     *     tags={"AFILIADO"},
     *     summary="LISTADO DE GRADOS DEL AFILIADO",
     *     operationId="getGrados",
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
     * Get list of degrees
     *
     * @param Request $request
     * @return void
     */
    public function index()
    {
        return Degree::orderBy('name')->get();
    }
    
    public function show(Degree $degree)
    {
        return $degree;
    }
}
