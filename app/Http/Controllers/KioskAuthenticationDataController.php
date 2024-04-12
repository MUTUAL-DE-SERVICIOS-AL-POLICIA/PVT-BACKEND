<?php

namespace App\Http\Controllers;

use App\Models\kiosk\KioskAuthenticationData as KioskKioskAuthenticationData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    public function save_photo(Request $request)
    {
        $request->validate([
            'photo_ci' => 'image|max:2048',
            'photo_face' => 'image|max:2048',
            'affiliate_id' => 'required'
        ]);
        $basePathIdentityCard = 'ci';
        $basePathFace = 'face';
        $subdirectory = $request->affiliate_id;
        if ($request->photo_ci != null) {
            if (!Storage::disk('custom_storage')->exists("$basePathIdentityCard/$subdirectory")) {
                Storage::disk('custom_storage')->makeDirectory("$basePathIdentityCard/$subdirectory");
            }
        $photoIdentityCard = $request->file('photo_ci');
        $filenameIdentityCard = 'ci_anverso_' . now()->format('YmdHis') . '.' . $photoIdentityCard->getClientOriginalExtension();
        $path = Storage::disk('custom_storage')->put("$basePathIdentityCard/$subdirectory/$filenameIdentityCard", file_get_contents($photoIdentityCard));
        }
        if ($request->photo_face != null) {
            if (!Storage::disk('custom_storage')->exists("$basePathFace/$subdirectory")) {
                Storage::disk('custom_storage')->makeDirectory("$basePathFace/$subdirectory");
            }
            $photoFace = $request->file('photo_face');
            $filenameFace = 'rostro_' . now()->format('YmdHis') . '.' . $photoFace->getClientOriginalExtension();
            $path = Storage::disk('custom_storage')->put("$basePathFace/$subdirectory/$filenameFace", file_get_contents($photoFace));
        }
        return response()->json(['path' => $path], 201);
    }
}
