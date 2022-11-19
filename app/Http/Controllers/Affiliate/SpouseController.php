<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate\Spouse;
use App\Http\Requests\Affiliate\SpouseRequest;

class SpouseController extends Controller
{

    public function show(Spouse $spouse)
    {
        return $spouse;
    }
     /**
     * @OA\Post(
     *      path="/api/affiliate/spouse",
     *      tags={"AFILIADO"},
     *      summary="CREAR CONYUGUE",
     *      operationId="CrearCOnyugue",
     *      description="Crear conyugue",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=false,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="first_name", type="string",description="primer nombre - required",example="ANA" ),
     *              @OA\Property(property="last_name", type="string",description="apellido paterno",example="ALVAREZ"),
     *              @OA\Property(property="city_identity_card_id", type="integer",description="id de la ciudad del CI - required",example=2),
     *              @OA\Property(property="affiliate_id", type="integer",description="id del afiliado - required",example="8"),
     *              @OA\Property(property="identity_card", type="string",description="carnet de identidad - required",example="10000000"),
     *              @OA\Property(property="civil_status", type="string",description="estado civil - required",example="C"),
     *              @OA\Property(property="city_birth_id", type="integer",description="id de la ciudad de nacimiento - required",example=4),
     *              @OA\Property(property="birth_date", type="date",description="fecha de nacimiento - required",example="1944-08-08"),
     *              @OA\Property(property="second_name", type="string",description="segundo nombre",example=""),
     *              @OA\Property(property="mothers_last_name", type="string",description="apellido materno",example="CURCUY"),
     *              @OA\Property(property="due_date", type="date",description="fecha de vencimiento del CI",example=""),
     *              @OA\Property(property="marriage_date", type="date",description="fecha de matrimonio",example="2017-04-01"),
     *              @OA\Property(property="surname_husband", type="string",description="apellido de casada",example=""),
     *              @OA\Property(property="date_death", type="date",description="fecha de fallecimiento",example="2022-02-02"),
     *              @OA\Property(property="reason_death", type="string",description="causa de fallecimiento",example=""),
     *              @OA\Property(property="death_certificate_number", type="integer",description="número de certificado de fallecimiento",example=1111111),
     *          )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *            type="object"
     *         )
     *      )
     * )
     *
     * @return void
     */

    public function store(SpouseRequest $request)
    {
        return Spouse::create($request->all());
    }
 /**
     * @OA\Patch(
     *      path="/api/affiliate/spouse/{spouse}",
     *      tags={"AFILIADO"},
     *      summary="ACTUALIZAR CONYUGUE",
     *      operationId="ActualizarCOnyugue",
     *      description="Actualizar conyugue",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="spouse",
     *         in="path",
     *         description="",
     *         required=true,
     *         example=1,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
     *      @OA\RequestBody(
     *          required=false,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="first_name", type="string",description="primer nombre",example="ANA" ),
     *              @OA\Property(property="last_name", type="string",description="apellido paterno",example="ALVAREZ"),
     *              @OA\Property(property="city_identity_card_id", type="integer",description="id de la ciudad del CI",example=2),
     *              @OA\Property(property="affiliate_id", type="integer",description="id del afiliado",example="8"),
     *              @OA\Property(property="identity_card", type="string",description="carnet de identidad",example="10000000"),
     *              @OA\Property(property="civil_status", type="string",description="estado civil",example="C"),
     *              @OA\Property(property="city_birth_id", type="integer",description="id de la ciudad de nacimiento",example=4),
     *              @OA\Property(property="birth_date", type="date",description="fecha de nacimiento",example="1944-08-08"),
     *              @OA\Property(property="second_name", type="string",description="segundo nombre",example=""),
     *              @OA\Property(property="mothers_last_name", type="string",description="apellido materno",example="CURCUY"),
     *              @OA\Property(property="due_date", type="date",description="fecha de vencimiento del CI",example=""),
     *              @OA\Property(property="marriage_date", type="date",description="fecha de matrimonio",example="2017-04-01"),
     *              @OA\Property(property="surname_husband", type="string",description="apellido de casada",example=""),
     *              @OA\Property(property="date_death", type="date",description="fecha de fallecimiento",example="2022-02-02"),
     *              @OA\Property(property="reason_death", type="string",description="causa de fallecimiento",example=""),
     *              @OA\Property(property="death_certificate_number", type="integer",description="número de certificado de fallecimiento",example=1111111),
     *          )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *            type="object"
     *         )
     *      )
     * )
     *
     * @return void
     */
    public function update(SpouseRequest $request, Spouse $spouse)
    {
        $spouse->fill($request->all());
        $spouse->save();
        return $spouse;
    }
}
