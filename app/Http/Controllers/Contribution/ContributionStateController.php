<?php

namespace App\Http\Controllers\Contribution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contribution\ContributionState;

class ContributionStateController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/contribution/contribution_state",
     *      tags={"CONTRIBUCION"},
     *      summary="LISTADO DE ESTADOS DE APORTES",
     *      operationId="getContributionStates",
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
        return response()->json([
            'error' => false,
            'message' => 'Lista de estados de aportes',
            'data' => ContributionState::orderBy('name')->get()
        ], 200);

    }
}
