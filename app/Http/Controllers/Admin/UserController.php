<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\User;
use App\Models\Admin\Role;
use App\Models\Admin\Module;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\Admin\UserResource;
use Illuminate\Support\Facades\DB;
use Ldap;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

  /**
     * @OA\Info(
     *      version="1.0.0",
     *      title="PVT OpenApi",
     *      description="L5 Swagger OpenApi description"
     * )
     *
     */

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/user",
     *     tags={"USUARIO"},
     *     summary="LISTADO DE USUARIOS",
     *     operationId="getUsers",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Pagina a mostrar",
     *         required=false, 
     *       ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Por Pagina",
     *         example=10,
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="active",
     *         in="query",
     *         description="Usuarios activos(1) o inactivos(0) ",
     *         example=1,
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="first_name",
     *         in="query",
     *         description="Filtro por nombre de Usuario ",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="last_name",
     *         in="query",
     *         description="Filtro por Apellidos",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="position",
     *         in="query",
     *         description="Filtro por cargo",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="filtro por nombre de Usuario",
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
     * Get list of users.
     *
     * @param Request $request
     * @return void
     */

    public function index(Request $request){
        $active = request('active') ?? 1;
        $first_name = request('first_name') ?? '';
        $last_name = request('last_name') ?? '';
        $position = request('position') ?? '';
        $username = request('username') ?? '';
        $conditions = [];
        if ($position != '') {
          array_push($conditions, array('position', 'ilike', "%{$position}%"));
        }
        if ($first_name != '') {
            array_push($conditions, array('first_name', 'ilike', "%{$first_name}%"));      
        }
        if ($last_name != '') {
            array_push($conditions, array('last_name', 'ilike', "%{$last_name}%"));  
        }  
        if ($username != '') {
            array_push($conditions, array('username', 'ilike', "%{$username}%"));
        }  
        $per_page = $request->per_page ?? 10;
        $users = User::where('active',$active)->where($conditions)->paginate($per_page);
        return response()->json([
            'message' => 'Realizado con éxito',
            'payload' => [
                'users' => $users
            ],
        ]);
    }

    /**
     * @OA\SecurityScheme(
     *       securityScheme="bearerAuth",
     *       type="http",
     *       scheme="bearer"
     * )
     */

    //obtener del usuario sus modulos, roles y permisos
    public static function user_module_role_permission($id){
        if($id){
        $user = User::find($id);
        }else{
        $user = Auth::user();
        }
        $modules_objects = collect();
        $modules = $user->modules;
        if(isset($modules)){
            foreach($modules as $module){
                $module_object = Module::find($module);
                $module_object->roles = $user->roles;
                $module_object->roles = $user->rolesByModule($module);
                if(isset($module_object->roles)){
                    foreach($module_object->roles as $role){
                        $role_permissions = $role->permissions;
                    }
                }
                $module_object->roles->permissions = $role_permissions;
                $modules_objects->push($module_object);
            }
        }
        return $modules_objects;
      /*  return response()->json([
            'message' => 'Realizado con éxito',
            'payload' => [
                'modules' => $modules_objects
            ],
        ]);*/
    }
    /**
     * @OA\Post(
     *      path="/api/admin/user",
     *      tags={"USUARIO"},
     *      summary="NUEVO USUARIO",
     *      operationId="crear usuario",
     *      description="Creación de un nuevo usuario",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="username", type="string",description="nombre de usuario required"),
     *              @OA\Property(property="first_name", type="string",description="nombres required"),
     *              @OA\Property(property="last_name", type="string",description="apellidos required"),
     *              @OA\Property(property="identity_card", type="string",description="carnet de identidad required"),
     *              @OA\Property(property="position", type="string",description="Cargo del usuario required"),
     *              @OA\Property(property="phone", type="integer",description="telefono required"),
     *              @OA\Property(property="city_id", type="integer",description="id de ciudad"),
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

    public function store(UserRequest $request)
    {
        try{
            DB::beginTransaction();
            /*if(User::where('username',$request->username)->first())
            {
                $user = User::where('username', $request->username)->first();
                $user->username = $user->username.'_'.$user->id;
                $user->save();
            }*/
            $user = new User();
            $user->username = $request->username;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->position = $request->position;
            $user->phone = $request->phone;
            $user->city_id = $request->city_id;
            $user->password = Hash::make($request->identity_card);
            $user->identity_card = $request->identity_card;
            $user->active = true;
            $user->status = "active";
            $user->save();
            DB::commit();
            return response()->json([
                'message' => 'Realizado con éxito',
                'payload' => [
                    'user' => $user,
                ],
            ]);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el usuario',
                'payload' => [
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/user/{user}",
     *     tags={"USUARIO"},
     *     summary="DETALLE DE USUARIO",
     *     operationId="getUser",
     *     @OA\Parameter(
     *         name="user",
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
     * Get user
     *
     * @param Request $request
     * @return void
     */

    public function show(User $user)
    {
        return [
            'message' => 'Usuario encontrado',
            'payload' => [
                'user' => new UserResource($user),
            ]
         ];
    }
    /**
     * @OA\Patch(
     *     path="/api/admin/user/{user}/role",
     *     tags={"USUARIO"},
     *     summary="ESTABLECER O ELIMINAR EL ROL A UN USUARIO",
     *     operationId="setOrRemoveRolForUser",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="",
     *         required=true,
     *         example=240,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="role_id", type="integer",description="id rol required",example=1)
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *           type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Get user
     *
     * @param Request $request
     * @return void
     */

    public function set_or_remove_role(Request $request, User $user)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);
        DB::beginTransaction();
        try {
            $role= Role::find($request->role_id);
            $user_roles = $user->roles()->pluck('id');
            $add_role = true;
            foreach($user_roles as $user_role){
                if($user_role == $role->id){
                    $add_role = false;
                    break;
                }
            }
            if($add_role){
                $insert = "INSERT INTO role_user (role_id,user_id) VALUES ($role->id, $user->id)";
                $insert = DB::select($insert);
            }else{
                $delete = "DELETE from role_user where role_id =$role->id AND user_id = $user->id";
                $delete = DB::select($delete);
            }
            DB::commit();
            return response()->json([
                'message' => $add_role? 'Realizado con éxito la adición del rol: '.$role->display_name:'Realizado con éxito la eliminación del rol: '.$role->display_name,
                'payload' => [
                    'user' => new UserResource($user),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Ocurrio un error',
                'error' => $e
            ]);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/admin/user/{user}/module_role_state_user",
     *     tags={"USUARIO"},
     *     summary="LISTADO DE ROLES ASIGNADOS A UN USURIO POR MODULO",
     *     operationId="module_role_state_user",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
     *     @OA\Parameter(
     *         name="module_id",
     *         in="query",
     *         description="id del modulo",
     *         required=true,
     *       ),
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id rol",
     *         required=false,
     *       ),
     *     @OA\Parameter(
     *         name="action",
     *         in="query",
     *         description="Nombre de la accion a realizar",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filtro por nombre del rol ",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="display_name",
     *         in="query",
     *         description="Filtro del nombre de visualización del rol",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Pagina a mostrar",
     *         example=1,
     *         required=false,
     *       ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Por Pagina",
     *         example=10,
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *           type="object"
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * obtener listado roles asignados a un usuario por modulo
     *
     * @param Request $request
     * @return void
     */
    public function module_role_state_user(Request $request, User $user)
    {  
        $request->validate([
            'module_id' => 'required|integer|exists:modules,id'
        ]);

        $id = request('id') ?? '';
        $display_name = request('display_name') ?? '';
        $action = request('action') ?? '';
        $name = request('name') ?? '';
        
        $conditions = [];

        if ($id != '') array_push($conditions, array('id', '=', "{$id}"));
        if ($display_name != '') array_push($conditions, array('display_name', 'ilike', "%{$display_name}%"));
        if ($action != '') array_push($conditions, array('action', 'ilike', "%{$action}%"));
        if ($name != '') array_push($conditions, array('name', 'ilike', "%{$name}%"));

        $per_page = $request->per_page ?? 10;

        $active = false;
        $user_role_asignes = $user->rolesByModule($request->module_id)->pluck('id');
        $module_roles = Role::where('module_id', $request->module_id)->where($conditions)->paginate($per_page);

        foreach ($module_roles as $module_role) {
            $contar_active = 0;
            foreach ($user_role_asignes as $user_role_asigne){
                if($module_role->id == $user_role_asigne) $contar_active++;
            }
            if($contar_active == 1) $active = true;
            else $active = false;
            $module_role->active = $active;
        }
        return response()->json([
            'message' => 'Realizado con éxito',
            'payload' => [
                'role' => $module_roles,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/get_employees",
     *     tags={"USUARIO"},
     *     summary="OBTENER LOS EMPLEADOS NO REGISTRADOS EN USUARIOS",
     *     operationId="get_employees",
     *     description="Obtiene los empleados no registrados en usuarios",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="ok",
     *     @OA\JsonContent(
     *     type="object"
     *     )
     * )
     * )
     */
    public function get_employees()
    {
        $ldap_entries = new Ldap();
        $users = array();
        foreach($ldap_entries->list_entries() as $ldap_entry)
        {
            $employee = json_decode(Http::get(env('MIX_RRHH_URL').'employee/'.$ldap_entry->employeeNumber));
            $user = User::where('username', trim($ldap_entry->uid))->where('first_name', trim($ldap_entry->givenName))->where('last_name', trim($ldap_entry->sn))->first();
            if(!$user)
            {
                array_push($users, array(
                    'city_id' => trim($employee->city_identity_card_id),
                    'username' => trim($ldap_entry->uid),
                    'first_name' => trim($ldap_entry->givenName),
                    'last_name' => trim($ldap_entry->sn),
                    'identity_card' => trim($employee->identity_card),
                    'position' => trim($ldap_entry->title),
                    'phone' => trim($employee->phone_number),
                ));
            }
        }
        return response()->json([
            'message' => 'Realizado con éxito',
            'payload' => [
                'employees' => $users,
            ],
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/admin/sync_employees",
     *     tags={"USUARIO"},
     *     summary="SINCRONIZACION DE USUARIOS Y LDAP",
     *     operationId="sync_employees",
     *     description="sincronizacion de usuarios",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="ok",
     *     @OA\JsonContent(
     *     type="object"
     *     )
     * )
     * )
     */
    public function sync_employees()
    {
        try
        {
            $ldap = new Ldap();
            // obtiene todos los empleados del ldap
            $ldap_entries = $ldap->list_entries();
            $users_ldap = array();
            foreach($ldap_entries as $ldap_entry){
                $entry = json_decode(file_get_contents(env("MIX_RRHH_URL")."employee/".$ldap_entry->employeeNumber));
                $user = User::where('username', $ldap_entry->uid)->first();
                if(!$user)
                {
                    $user = array(
                        "username" => trim($ldap_entry->uid),
                        "first_name" => trim($ldap_entry->givenName),
                        "last_name" => trim($ldap_entry->sn),
                        "position" => trim($ldap_entry->title),
                        "identity_card" => trim($entry->identity_card),
                        "phone" => trim($entry->phone_number)
                    );
                    array_push($users_ldap,$user);
                }
                elseif(User::where('identity_card',$entry->identity_card)->count() == 0)
                {
                    $user = array(
                        "username" => trim($ldap_entry->uid),
                        "first_name" => trim($ldap_entry->givenName),
                        "last_name" => trim($ldap_entry->sn),
                        "position" => trim($ldap_entry->title),
                        "identity_card" => trim($entry->identity_card),
                        "phone" => trim($entry->phone_number)
                    );
                    array_push($users_ldap,$user);
                }
            }
            return response()->json([
                'message' => 'Realizado con éxito',
                'payload' => [
                    'new_users_ldap' => $users_ldap,
                ],
            ]);
        }
        catch(Exception $e)
        {
            return response()->json([
                'message' => 'Error al sincronizar los empleados',
                'payload' => [
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }
    /**
     * @OA\Patch(
     *      path="/api/admin/user/{user}",
     *      tags={"USUARIO"},
     *      summary="ACTUALIZAR USUARIO",
     *      operationId="ActualizarUsuario",
     *      description="Actualizar usuario",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="user",
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
     *          description= "Provide auth credentials",
     *          required=false,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="city_id", type="integer",description="id de ciudad",example="1"),
     *              @OA\Property(property="first_name", type="string",description="nombres",example="ALEJANDRO ERICK"),
     *              @OA\Property(property="last_name", type="string",description="apellidos",example="GUISBERT FLOR"),
     *              @OA\Property(property="username", type="string",description="nombre de usuario",example="aguisbert"),
     *              @OA\Property(property="position", type="string",description="Cargo del usuario",example="Jefe de Unidad de Sistemas y Soportes Técnico"),
     *              @OA\Property(property="is_commission", type="boolean",description="usuario en comision",example="false"),
     *              @OA\Property(property="phone", type="string",description="telefono ",example="78773841"),
     *              @OA\Property(property="active", type="boolean",description="Usuario activo",example="true"),
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

    public function update(UserRequest $request, User $user)
    {
        $user->fill($request->all());
        $user->save();
        return response()->json([
            'message' => 'Usuario actualizado',
            'payload' => [
                'user' => $user,
            ],
        ]);
    }

    public function update_users_ci()
    {
        try{
            $ldap = new Ldap();
            $ldap_entries = $ldap->list_entries();
            $c = 0;
            foreach($ldap_entries as $ldap_entry){
                $entry = json_decode(file_get_contents(env("MIX_RRHH_URL")."employee/".$ldap_entry->employeeNumber));
                $user = User::where('username', $ldap_entry->uid)->first();
                if($user)
                {
                    $user->identity_card = trim($entry->identity_card);
                    $user->position = trim($ldap_entry->title);
                    $user->save();
                    $c++;
                }
            }
            return $c." Usuarios actualizados";
        }
        catch(Exception $e)
        {
            return response()->json([
                'message' => 'Error al sincronizar los empleados',
                'payload' => [
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }
}
