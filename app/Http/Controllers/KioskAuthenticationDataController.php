<?php

namespace App\Http\Controllers;

use App\Models\kiosk\KioskAuthenticationData as KioskKioskAuthenticationData;
use Illuminate\Http\Request;

class KioskAuthenticationDataController extends Controller
{
    public function register_auth_kiosk(Request $request)
    {
        $request->validate([
            'identity_card' => 'required|string',
            'left_text' => 'required|string',
            'middle_text' => 'required|string',
            'right_text' => 'required|string',
            'ocr_state' => 'required|boolean',
            'facial_recognition' => 'required|boolean',
        ]);
        $kioskAuthenticationData = KioskKioskAuthenticationData::create([
            'identity_card' => $request->input('identity_card'),
            'affiliate_id' => $request->input('affiliate_id'),
            'left_text' => $request->input('left_text'),
            'middle_text' => $request->input('middle_text'),
            'right_text' => $request->input('right_text'),
            'ocr_state' => $request->input('ocr_state'),
            'facial_recognition' => $request->input('facial_recognition'),
        ]);

        return response()->json(['message' => 'Registro exitoso', 'data' => $kioskAuthenticationData], 201);
    }
}
