<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\Http\Requests\Auth\AuthRequest;
use App\Models\Admin\User;
use App\Http\Resources\Admin\UserResource;
use Ldap;

class AuthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auth/auth_user",
     *     tags={"AUTENTICACIÓN"},
     *     summary="OBTENER USUARIO AUTENTICADO",
     *     operationId="getuser",
     *     description="Obtiene el usuario autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="ok",
     *     @OA\JsonContent(
     *     type="object"
     *     )
     *   )
     * )
     */
    public function index()
    {
        //return Auth::user();
        $user = Auth::user();
        return response()->json([
            'message' => 'ok',
            'payload' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/auth/login",
     *      tags={"AUTENTICACIÓN"},
     *      summary="ACCESO AL SISTEMA",
     *      operationId="login",
     *      description="Acceso al sistema con el token",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="username", type="string",description="username"),
     *              @OA\Property(property="password", type="string",description="password")
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
     * Logs user into the system.
     *
     * @param Request $request
     * @return void
    */
    public function login(AuthRequest $request)
    {
        $user = User::whereUsername($request->username)->where('active',true)->first();
        if ($user) {
            if (env('APP_ENV') == 'production') {
                $ldap = new Ldap();
                if($ldap->verify_open_port())
                {
                    if($ldap->bind($request->username, $request->password))
                    {
                        $tokens = $user->tokens()->count();
                        $token = $user->createToken('api')->plainTextToken;
                        $user->remember_token = $token;
                        $user->save();
                        return [
                            'message' => 'Sesión iniciada',
                            'payload' => [
                                'access_token' => $token,
                                'token_type' => 'Bearer',
                                'user' => new UserResource($user),
                            ],
                        ];
                    }
                }
            }else{
                $token = $user->createToken('api')->plainTextToken;
                $user->remember_token = $token;
                $user->save();
                return [
                    'message' => 'Sesión iniciada',
                    'payload' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'user' => new UserResource($user),
                    ],
                ];
            }
        }
        return response()->json([
            'message' => 'Credenciales inválidas',
            'errors' => [
                'username' => ['Usuario o contraseña incorrecta']
            ]
        ], 401);
    }
        /**
     * @OA\Post(
     *      path="/api/auth/login_ext",
     *      tags={"AUTENTICACIÓN"},
     *      summary="ACCESO AL SISTEMA",
     *      operationId="login_ext",
     *      description="Acceso al sistema con el token de manera externa",
     *      @OA\RequestBody(
     *          description= "Provide auth credentials",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="username", type="string",description="username"),
     *              @OA\Property(property="password", type="string",description="password")
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
     * Logs user into the system.
     *
     * @param Request $request
     * @return void
    */
    public function login_ext(Request $request)
    {
        $user = User::whereUsername($request->username)->first();
        if ($user->username == env('USER_EXT')) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('api')->plainTextToken;
                $user->remember_token = $token;
                $user->save();
                return [
                    'message' => 'Sesión iniciada',
                    'payload' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'user' => new UserResource($user),
                    ],
                ];
            }
        }
        return response()->json([
            'message' => 'Credenciales inválidas',
            'errors' => [
                'username' => ['Usuario o contraseña incorrecta']
            ]
        ], 401);
    }
    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"AUTENTICACIÓN"},
     *     summary="CERRAR SESIÓN",
     *     operationId="logout",
     *     description="Cierra la sesion actual del usuario",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Sesion cerrada correctamente",
     *     @OA\JsonContent(
     *     type="object"
     *     )
     *   )
     * )
     */

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete(); //cod anterior
       /* Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();*/

        return [
            'message' => 'Has cerrado sesión correctamente y el token se ha eliminado correctamente.'
        ];
    }

     /**
     * @OA\Patch(
     *     path="/api/auth/refresh",
     *     tags={"AUTENTICACIÓN"},
     *     summary="REFRESCAR TOCKEN",
     *     operationId="refresh",
     *     description="Refresca la sesion actual del usuario",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Refrescado correctamente",
     *     @OA\JsonContent(
     *     type="object"
     *     )
     *   )
     * )
     */
    public function refresh()
    {
        $user = Auth::user();
        Auth::user()->tokens()->delete();

        return response()->json([
            'message' => 'Sesión refrescada',
            'payload' => [
                'access_token' => Auth::user()->createToken('api')->plainTextToken,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function guard()
    {
        return Auth::Guard('api');
    }
}
