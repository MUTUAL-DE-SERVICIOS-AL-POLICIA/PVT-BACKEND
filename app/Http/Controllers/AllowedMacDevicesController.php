<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AllowedMacDevices;
use App\Models\Admin\User;
use App\Http\Resources\Admin\UserResource;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\Spouse;

class AllowedMacDevicesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/global/allowed_mac_device",
     *     tags={"DIRECCION MAC PERMITIDA"},
     *     summary="LISTADO DE DIRECCIONES MAC PERMITIDAS",
     *     operationId="getAllowedMacAdress",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *         type="object"
     *         )
     *     )
     * )
     *
     * Get list of mac adress
     *
     * @param Request $request
     * @return void
     */
    public function index()
    {
        return AllowedMacDevices::orderBy('id')->get();
    }

    public function show(AllowedMacDevices $allowed_mac_device)
    {
        return $allowed_mac_device;
    }

    public function store($request)
    {
        //
    }

    public function update(AllowedMacDevices $allowed_mac_device)
    {
        //
    }

    public function destroy(AllowedMacDevices $allowed_mac_device)
    {
        //
    }

    public function get_session(Request $request)
    {
        $request->validate([
            'device_name' => 'required|exists:allowed_mac_devices,device_name',
            'identity_card'=> 'required'
        ]);
        $device = AllowedMacDevices::where('device_name', $request->device_name)->where('is_enable', true)->first();
        if($device){
            if($this->search_affiliate($request->identity_card) <> null)
            {
                $token = $device->createToken('api')->plainTextToken;
                $device->api_token = $token;
                $device->save();
                $beneficiary = $this->search_affiliate($request->identity_card);
                if($beneficiary)
                {
                    $nup = $beneficiary->affiliate_id ? $beneficiary->affiliate_id : $beneficiary->id;
                    $full_name = $beneficiary->full_name;
                    $degree = $beneficiary->affiliate_id ? '' : $beneficiary->title;
                }
                return [
                    'message' => 'Sesión iniciada',
                    'payload' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'user' => $device,
                        'nup' => $nup,
                        'full_name' => $full_name,
                        'degree' => $degree
                    ],
                ];
            }else{
                return response()->json([
                    'message' => 'Carnet inexistente',
                    'errors' => [
                        'identity_card' => ['CI no encontrado']
                    ]
                    ], 404);
            }
        } else {
            return response()->json([
            'message' => 'Dispositivo no permitido',
            'errors' => [
                'mac_address' => ['no permitido']
            ]
            ], 401);
        }
    }

    private function search_affiliate($identity_card)
    {
        $nup = null;
        if(Affiliate::where('identity_card', $identity_card)->first())
            return Affiliate::where('identity_card', $identity_card)->first();
        if(Spouse::where('identity_card', $identity_card)->first())
            return Spouse::where('identity_card', $identity_card)->first();
        return $nup;
    }
}
