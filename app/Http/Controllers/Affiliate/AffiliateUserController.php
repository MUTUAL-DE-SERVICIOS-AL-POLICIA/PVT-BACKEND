<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\AffiliateToken;
use App\Models\Affiliate\AffiliateUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpParser\Node\Stmt\Return_;

class AffiliateUserController extends Controller
{
    public static function  Generate_pin()
    {
        $referal_code = random_int(1000, 9999);
        return $referal_code;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    public function store(Request $request, $id)
    {
        $request['id'] = $id;
        $request->validate([
            'id' => 'required|integer|exists:affiliates,id'
        ]);
        $AffiliateId = $request->id;
        $isAffiliateToken = DB::table('affiliate_tokens')->where('affiliate_id', $AffiliateId)->exists();
        if (!$isAffiliateToken) {
            $AffiliateToken = new AffiliateToken;
            $AffiliateToken->affiliate_id = $AffiliateId;
            $AffiliateToken->save();
            $affilliate = Affiliate::find($AffiliateToken->affiliate_id);
            $user = new AffiliateUser;
            $user->affiliate_token_id = $AffiliateToken->id;
            $user->username = $affilliate->identity_card;
            $password=$this->Generate_pin();
            $user->password = Hash::make($password);
            $user->save();
            return response()->json([
                'message' => 'Usuario Registrado Exitosamente',
                'payload' => [
                    'user'=> $user->username,
                    'pin' => $password,
                    'id' => $AffiliateToken->affiliate_id
                ],
            ]);
        }
        else {
            $AffiliateToken = AffiliateToken::where('affiliate_id',$AffiliateId)->first();
            $isAffiliateUser = DB::table('affiliate_users')->where('affiliate_token_id', $AffiliateToken->id)->exists();
            if ($isAffiliateUser) {
                return response()->json([
                    'message' => 'El Usuario ya estaba registrado'
                ]);
            }
            else {
                $affilliate = Affiliate::where('id',$AffiliateToken->affiliate_id)->first();
                $user = new AffiliateUser;
                $user->affiliate_token_id = $AffiliateToken->id;
                $user->username = $affilliate->identity_card;
                $password=$this->Generate_pin();
                $user->password = Hash::make($password);
                $user->save();
                return response()->json([
                    'message' => 'Usuario Registrado Exitosamente',
                    'payload' => [
                        'user'=> $user->username,
                        'pin' => $password,
                        'id' => $AffiliateToken->affiliate_id
                    ],
                ]);
            }
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Affiliate\AffiliateUser  $affiliateUser
     * @return \Illuminate\Http\Response
     */
    public function show(AffiliateUser $affiliateUser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Affiliate\AffiliateUser  $affiliateUser
     * @return \Illuminate\Http\Response
     */
    public function edit(AffiliateUser $affiliateUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Affiliate\AffiliateUser  $affiliateUser
     * @return \Illuminate\Http\Response
     */

    public static function auth(Request $request){
        $request->validate([
            'username' => 'required|integer',
            'password' => 'required',
            'device_id' => 'required',
            'firebase_token' => 'required'
        ]);

        $isAffiliateUser = DB::table('affiliate_users')->where('username', $request->username)->exists();
        if($isAffiliateUser){
            $AffiliateUser=AffiliateUser::where('username',$request->username)->first();
            $state=$AffiliateUser->access_status;
            $password=$AffiliateUser->password;
            if ($state=='Pendiente') {
                if (Hash::check($request->password,$password)) {
                    return response()->json([
                        "error"=> 'false',
                        'message' => 'Acceso Correcto cambie la password',
                        'data'=> [
                            'status'=> $state
                        ]
                    ]);
                }
                else {
                    return response()->json([
                        "error"=> 'true',
                        'message' => 'El pin es incorrecto',
                        'data'=> [
                            'status'=> $state
                            ]
                    ],403);
                }
            }
            else {
                if (Hash::check($request->password,$password)) {
                $AffiliateToken=AffiliateToken::find($AffiliateUser->affiliate_token_id);
                // $AffiliateToken->device_id=$request->device_id;
                $AffiliateToken->api_token=Hash::make($request->device_id);
                $token=$AffiliateToken;
                $AffiliateToken->firebase_token=$request->firebase_token;
                $AffiliateToken->save();
                return response()->json(
                    [
                        "error"=> 'false',
                        'message' => 'Acceso Correcto',
                        'data'=> [
                            'user'=>$token,
                            'status'=> $state
                        ]
                        ]
                    );
                }

                else {
                    return response()->json([
                        "error"=> 'true',
                        'message' => 'El password es incorrecto',
                        'data'=> [
                            'status'=> $state
                            ]
                    ],403);
                }
            }
        }
        else{
            return response()->json([
                "error"=> 'true',
                'message' => 'El username es incorrecto',
                'data'=> [

                    ]
            ],403);
        }
    }
    public function change_password(Request $request){
        $request->validate([
            'username' => 'required|integer|exists:affiliate_users,username',
            'password' => 'required',
            'new_password' => 'required',
            'device_id' => 'required',
            'firebase_token' => 'required'
        ]);
        $AffiliateUser=AffiliateUser::where('username',$request->username)->first();
        // $state=$AffiliateUser->access_status;
        $password=$AffiliateUser->password;
        if (Hash::check($request->password,$password)){
            $AffiliateUser->password=Hash::make($request->new_password);
            $AffiliateUser->access_status='Activo';
            $state=$AffiliateUser->access_status;
            $AffiliateUser->save();
            $AffiliateToken=AffiliateToken::find($AffiliateUser->affiliate_token_id);
            $AffiliateToken->api_token=Hash::make($request->device_id);
            $AffiliateToken->firebase_token=$request->firebase_token;
            $AffiliateToken->save();
            return response()->json(
                [
                    'message' => 'contraseña actualizada Correctamente ',
                    'data'=> [
                        'status'=> $state
                    ]
                ]);
        }
        else {
            return response()->json(
                [
                    'message' => 'Token invalido '
                ],403);
        }
    }

    public function update(Request $request, AffiliateUser $affiliateUser)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Affiliate\AffiliateUser  $affiliateUser
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->affiliate->affiliate_token()->update(['api_token' => null]);
        return response()->json([
            'error' => false,
            'message' => 'Sesión terminada',
            'data' => (object)[]
        ], 200);
    }
}
