<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Affiliate\AffiliateRequest;
use Illuminate\Http\Request;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\AffiliateToken;
use App\Models\Affiliate\AffiliateUser;
use App\Models\Affiliate\Spouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AffiliateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/affiliate/affiliate",
     *     tags={"AFILIADO"},
     *     summary="LISTADO DE AFILIADOS",
     *     operationId="getAffiliates",
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
     *         name="identity_card_affiliate",
     *         in="query",
     *         description="Filtro por Cédula de Identidad",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="registration_affiliate",
     *         in="query",
     *         description="Filtro por Matrícula",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="full_name_affiliate",
     *         in="query",
     *         description="Filtro por Nombre o Apellido",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="name_degree",
     *         in="query",
     *         description="Filtro por grado del Afiliado",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="name_affiliate_state",
     *         in="query",
     *         description="Filtro por estado del Afiliado",
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
        $identity_card_affiliate = request('identity_card_affiliate') ?? '';
        $registration_affiliate = request('registration_affiliate') ?? '';
        $full_name_affiliate  = request('full_name_affiliate') ?? '';
        $name_degree = request('name_degree') ?? '';
        $name_affiliate_state = request('name_affiliate_state') ?? '';

        $conditions = [];

        if ($id_affiliate != '') {
            array_push($conditions, array('view_affiliates.id_affiliate', $id_affiliate));
        }
        if ($identity_card_affiliate != '') {
            array_push($conditions, array('view_affiliates.identity_card_affiliate', 'ilike', "%{$identity_card_affiliate}%"));
        }
        if ($registration_affiliate != '') {
            array_push($conditions, array('view_affiliates.registration_affiliate', 'ilike', "%{$registration_affiliate}%"));
        }
        if ($full_name_affiliate != '') {
            array_push($conditions, array('view_affiliates.full_name_affiliate', 'ilike', "%{$full_name_affiliate}%"));
        }
        if ($name_degree != '') {
            array_push($conditions, array('view_affiliates.name_degree', 'ilike', "%{$name_degree}%"));
        }
        if ($name_affiliate_state != '') {

            array_push($conditions, array('view_affiliates.name_affiliate_state', 'ilike', "%{$name_affiliate_state}%"));
        }

        $order = request('sortDesc') ?? '';
        if ($order != '') {
            if ($order) {
                $order_affiliate = 'Asc';
            }
            if (!$order) {
                $order_affiliate = 'Desc';
            }
        } else {
            $order_affiliate = 'Desc';
        }

        $per_page = $request->per_page ?? 10;
        $affiliates = DB::table('view_affiliates')
            ->where($conditions)
            ->select('*')
            ->orderBy('full_name_affiliate', $order_affiliate)
            ->paginate($per_page);

        return response()->json([
            'message' => 'Realizado con éxito',
            'payload' => [
                'affiliates' => $affiliates
            ],
        ]);
    }

    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/affiliate/affiliate/{affiliate_id}",
     *     tags={"AFILIADO"},
     *     summary="DETALLE DEL AFILIADO",
     *     operationId="getAffiliate",
     *     @OA\Parameter(
     *         name="affiliate_id",
     *         in="path",
     *         description="",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *            type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get affiliate
     *
     * @param Request $request
     * @return void
     */
    public function show(Request $request, Affiliate $affiliate)
    {
        $affiliate->full_name = $affiliate->full_name;
        $affiliate->civil_status_gender = $affiliate->civil_status_gender;
        $affiliate->identity_card_ext = $affiliate->identity_card_ext;
        $affiliate->degree = $affiliate->degree;
        $affiliate->category = $affiliate->category;
        $affiliate->unit = $affiliate->unit;
        $affiliate->addresses = $affiliate->addresses;

        if (isset($affiliate->addresses)) {
            foreach ($affiliate->addresses as $address) {
                if (isset($address->city))
                    $affiliate->addresses->city = $address->city;
                else
                    $affiliate->addresses->city = null;
            }
        }
        $affiliate->cell_phone_number = explode(',', $affiliate->cell_phone_number);

        if ($affiliate->spouse) {
            $affiliate->spouse = $affiliate->spouse;
            $affiliate->dead_spouse = $affiliate->spouse->dead;
        } else {
            $affiliate->spouse = [];
            $affiliate->dead_spouse = null;
        }
        if ($affiliate->affiliate_state != null) $affiliate->affiliate_state;
        if ($affiliate->affiliate_state != null) $affiliate->dead = $affiliate->dead;
        $affiliate->credential_status = $this->credential_status($request, $affiliate->id);

        return $affiliate;

        // return response()->json([
        //     'message' => 'Realizado con éxito',
        //     'payload' => [
        //         'affiliate' => $affiliate
        //     ],
        // ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * @OA\Patch(
     *      path="/api/affiliate/affiliate/{affiliate_id}",
     *      tags={"AFILIADO"},
     *      summary="ACTUALIZAR AFILIADO",
     *      operationId="ActualizarAfiliado",
     *      description="Actualizar afiliado",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="affiliate_id",
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
     *              @OA\Property(property="first_name", type="string",description="primer nombre",example="GARY"),
     *              @OA\Property(property="second_name", type="string",description="segundo nombre",example=""),
     *              @OA\Property(property="last_name", type="string",description="apellido paterno",example="ALVAREZ"),
     *              @OA\Property(property="mothers_last_name", type="string",description="apellido materno",example="CURCUY"),
     *              @OA\Property(property="surname_husband", type="string",description="apellido de casada",example=""),
     *              @OA\Property(property="gender", type="string",description="género",example="M"),
     *              @OA\Property(property="civil_status", type="string",description="estado civil",example="C"),
     *              @OA\Property(property="birth_date", type="date",description="fecha de nacimiento",example="1944-08-08"),
     *              @OA\Property(property="city_birth_id", type="integer",description="id de la ciudad de nacimiento",example=4),
     *              @OA\Property(property="affiliate_state_id", type="integer",description="id de estado de afiliado",example=5),
     *              @OA\Property(property="date_entry", type="date",description="fecha de ingreso a la policía",example="2010-07-01"),
     *              @OA\Property(property="degree_id", type="integer",description="id del grado policial",example=5),
     *              @OA\Property(property="unit_id", type="integer",description="id de la unidad de destino",example=1),
     *              @OA\Property(property="unit_police_description", type="string",description="descripcion de la unidad policial",example=""),
     *              @OA\Property(property="category_id", type="integer",description="id de la categoría",example="8"),
     *              @OA\Property(property="pension_entity_id", type="integer",description="id de la entidad de pensiones",example="1"),
     *              @OA\Property(property="date_derelict", type="date",description="fecha de baja de la policía",example="2017-04-01"),
     *              @OA\Property(property="city_identity_card_id", type="integer",description="id de la ciudad del CI",example=2),
     *              @OA\Property(property="identity_card", type="string",description="carnet de identidad",example="1020566"),
     *              @OA\Property(property="registration", type="string",description="matrícula",example="440808ACG"),
     *              @OA\Property(property="date_death", type="date",description="fecha de fallecimiento",example="2022-02-02"),   
     *              @OA\Property(property="reason_death", type="string",description="causa de fallecimiento",example=""),
     *              @OA\Property(property="due_date", type="date",description="fecha de vencimiento del CI",example=""),
     *              @OA\Property(property="is_duedate_undefined", type="boolean",description="si la fecha de vencimiento de CI es indefinido",example=true),
     *              @OA\Property(property="nua", type="integer",description="número de NUA",example=1301101),     
     *              @OA\Property(property="account_number", type="integer",description="número de cuenta del afiliado",example=10000017711404),     
     *              @OA\Property(property="financial_entity_id", type="integer",description="id de la entidad financiera de la cuenta del afiliado",example=1),  
     *              @OA\Property(property="sigep_status", type="string",description="estado de la cuenta SIGEP",example="ACTIVO")
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
    public function update(AffiliateRequest $request, $id)
    {
        if (!Auth::user()->can('update-affiliate-primary')) {
            $update = $request->except('first_name', 'second_name', 'last_name', 'mothers_last_name', 'surname_husband', 'identity_card','category_id','degree_id','affiliate_state_id');
        } else {
            $update = $request->all();
        }
        $affiliate = Affiliate::findOrFail($id);
        $update = $request->except('cell_phone_number');
        $affiliate->fill($update);
        $affiliate->save();
        if ($request->has('cell_phone_number') && $request->cell_phone_number != null) {
            $phones = $request->cell_phone_number;
            $affiliate->cell_phone_number = implode(',', $phones);
            $affiliate->save();
        }

        return response()->json([
            'message' => 'Datos modificados con éxito',
            'payload' => [
                'affiliate' => $affiliate
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/affiliate/affiliate/{affiliate}/address",
     *     tags={"AFILIADO"},
     *     summary="LISTADO DE DIRECCIONES DEL AFILIADO",
     *     operationId="getAddresses",
     * @OA\Parameter(
     *         name="affiliate",
     *         in="path",
     *         description="",
     *         example=1,
     *
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
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
     * Get affiliate addresses 
     *
     * @param Request $request
     * @return void
     */
    public function get_addresses(Affiliate $affiliate)
    {
        return $affiliate->addresses;
    }
    /**
     * @OA\Patch(
     *      path="/api/affiliate/affiliate/{affiliate}/address",
     *      tags={"AFILIADO"},
     *      summary="ACTUALIZAR DIRECCIONES DEL AFILIADO",
     *      operationId="UpdateAddressesAffiliate",
     *      description="Actualizar las direcciones del afiliado",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="affiliate",
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
     *              @OA\Property(property="addresses", type="array items: type:integer",description="Lista de IDs de direcciones",example="[28180,6291]"),
     *              @OA\Property(property="addresses_valid", type="int",description="dirección validada",example="28180")
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
    public function update_addresses(Request $request, Affiliate $affiliate)
    {
        $request->validate([
            'addresses' => 'array',
            'addresses.*' => 'exists:addresses,id',
            'addresses_valid' => 'nullable|integer'
        ]);

        $affiliate->save();

        if ($request->addresses != []) {
            if ($request->has('addresses_valid') && $request->addresses_valid != 0) {
                $affiliate->addresses()->sync($request->addresses);
                $affiliate->addresses()->sync([$request->addresses_valid => ['validated' => true]]);
                return $affiliate->addresses()->sync($request->addresses);
            } else {
                $affiliate->addresses()->sync($request->addresses);
                $affiliate->addresses()->sync([$affiliate->addresses->first()->id => ['validated' => true]]);
                return $affiliate->addresses()->sync($request->addresses);
            }
        } else {
            return "No hay direcciones por añadir";
        }
    }

    /**
     * @OA\Get(
     *     path="/api/affiliate/credential_status/{id}",
     *     tags={"AFILIADO"},
     *     summary="ESTADO DE CREDENCIALES - OFICINA VIRTUAL",
     *     operationId="getStatus",
     * @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="",
     *         example=1,
     *
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
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
     * Get status of virtual office.
     *
     * @param Request $request
     * @return void
     */
    public function credential_status(Request $request, $id)
    {
        $request['id'] = $id;
        $request->validate([
            'id' => 'required|integer|exists:affiliates,id'
        ]);

        $affiliate_token = AffiliateToken::whereAffiliateId($id)->first();
        $affiliate = Affiliate::find($id);
        if (isset($affiliate_token)) {
            $affiliate_user = AffiliateUser::where('affiliate_token_id', $affiliate_token->id)->first();
            if ($affiliate_user == NULL) {
                $access_status = "No asignadas";
                $account_type = null;
                $created_at = null;
                $updated_at = null;
            } else {
                $access_status = $affiliate_user->access_status;
                $account_type = ($affiliate_user->username == $affiliate->identity_card) ? 'Titular' : 'Viudedad';
                $created_at = $affiliate_user->created_at;
                $updated_at = $affiliate_user->updated_at;
            }
        } else {
            $access_status = "No asignadas";
            $account_type = null;
            $created_at = null;
            $updated_at = null;
        }
        return [
            'access_status' => $access_status,
            'account_type' => $account_type,
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ];
    }
    /**
     * @OA\Get(
     *     path="/api/affiliate/affiliate_record/{affiliate}",
     *     tags={"AFILIADO"},
     *     summary="RECORDS",
     *     operationId="getRecord",
     * @OA\Parameter(
     *         name="affiliate",
     *         in="path",
     *         description="",
     *         required=true,
     *         example=1,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
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
     * Get status of virtual office.
     *
     * @param Request $request
     * @return void
     */
    public function get_record(Affiliate $affiliate)
    {
        $affiliate_records = $affiliate->affiliate_records_pvt()->with(['user:id,username'])->orderByDesc('created_at')->get();
        $records = $affiliate->records()->with(['user:id,username'])->get();
        $affiliate_activities = $affiliate->activities()->with('user:id,username')->orderByDesc('created_at')->get();
        return compact('affiliate_records','records','affiliate_activities');
    }
    /**
     * @OA\Get(
     *     path="/api/affiliate/affiliate/{affiliate}/spouse",
     *     tags={"AFILIADO"},
     *     summary="CONYUGUE DE AFILIADO",
     *     operationId="getSpouse",
     *     description="Obtener datos conyugue",
     * @OA\Parameter(
     *         name="affiliate",
     *         in="path",
     *         description="",
     *         required=true,
     *         example=1,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
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
     * Get status of virtual office.
     *
     * @param Request $request
     * @return void
    */
    public function get_spouse(Affiliate $affiliate) {
        return response()->json($affiliate->spouse);
    }
}
