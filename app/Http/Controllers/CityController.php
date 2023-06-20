<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;

class CityController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/global/city",
     *     tags={"CIUDAD"},
     *     summary="LISTADO DE CIUDADES",
     *     operationId="getCiudades",
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
        return City::orderBy('name')->get();
    }

    public function show(City $city)
    {
        return $city;
    }
    /**
     * @OA\Get(
     *     path="/api/app/contacts",
     *     tags={"CIUDAD"},
     *     summary="LISTADO DE CONTACTOS DE CIUDADES",
     *     operationId="getContacts",
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
    public function listContacts()
    {
        return response()->json([
            'error' => false,
            'message' => 'Lista de ciudades',
            'data' => [
                'cities' => City::where('phone_prefix', '>', 0)->select('id', 'name', 'latitude', 'longitude', 'company_address', 'phone_prefix', 'company_phones', 'company_cellphones')->get()
            ]
        ], 200);
    }
}
