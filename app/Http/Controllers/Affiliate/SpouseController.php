<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate\Spouse;
use App\Http\Requests\Affiliate\SpouseRequest;
use Illuminate\Support\Facades\DB;

class SpouseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/affiliate/spouse",
     *     tags={"AFILIADO"},
     *     summary="LISTADO DE CONYUGES",
     *     operationId="getSpouses",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Página a mostrar",
     *         example=1,
     *         required=false,
     *       ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Por Página",
     *         example=10,
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="sortDesc",
     *         in="query",
     *         description="Vector de orden descendente(0) o ascendente(1)",
     *         example=1,
     *         required=false,
     *     ),
     *    @OA\Parameter(
     *         name="id_affiliate",
     *         in="query",
     *         description="Filtro por id del Afiliado",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="identity_card_spouses",
     *         in="query",
     *         description="Filtro por Cédula de Identidad",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="full_name_spouses",
     *         in="query",
     *         description="Filtro por Nombre o Apellido",
     *         required=false,
     *     ),
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
     * Get list of affiliates.
     *
     * @param Request $request
     * @return void
     */

    public function index(Request $request)
    {
        $id_affiliate = request('id_affiliate') ?? '';
        $identity_card_spouse = request('identity_card_spouses') ?? '';
        $full_name_spouse  = request('full_name_spouses') ?? '';
        $conditions = [];
        $values = [];
        if ($id_affiliate != '') {
            array_push($conditions, "affiliate_id  =?");
            array_push($values, "{$id_affiliate}");
        }
        if ($identity_card_spouse != '') {
            array_push($conditions, "identity_card ilike ?");
            array_push($values, "%{$identity_card_spouse}%");
        }
        if ($full_name_spouse != '') {
            array_push($conditions, "concat(first_name,second_name,last_name,mothers_last_name) ILIKE ?");
            $full_name_spouse= str_replace(' ', '', $full_name_spouse);
            array_push($values, "%{$full_name_spouse}%");
        }
        $query = DB::table('spouses');
        $order = request('sortDesc') ?? '';
        if ($order != '') {
            if ($order) {
                $order_year = 'asc';
            }
            if (!$order) {
                $order_year = 'desc';
            }
        } else {
            $order_year = 'desc';
        }
        $per_page = $request->per_page ?? 10;
        if (!empty($conditions)) {
            $query = $query->select('spouses.*', DB::raw("(concat(spouses.first_name,' ',spouses.second_name,' ',spouses.last_name,' ',spouses.mothers_last_name)) as full_name"))
                ->whereRaw(implode(" AND ", $conditions), $values);
        } else {
            $query = $query->select('spouses.*', DB::raw("(concat(spouses.first_name,' ',spouses.second_name,' ',spouses.last_name,' ',spouses.mothers_last_name)) as full_name"));
        }
        $results = $query->orderBy('full_name', $order_year)->paginate($per_page);
        return response()->json([
            'message' => 'Realizado con éxito',
            'payload' => [
                'spouses' => $results
            ],
        ]);
    }
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
