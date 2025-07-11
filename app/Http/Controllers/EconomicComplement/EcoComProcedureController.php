<?php

namespace App\Http\Controllers\EconomicComplement;

use App\Http\Controllers\Controller;
use App\Models\EconomicComplement\EcoComBeneficiary;
use App\Models\EconomicComplement\EcoComProcedure;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EcoComProcedureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $eco_com_procedures = EcoComProcedure::orderBy('year')->get();;
        $eco_com_procedure_list = collect();
        foreach ($eco_com_procedures as $eco_com_procedure) {
            $eco_com_procedure_object = new \stdClass();
            $eco_com_procedure_object->id = $eco_com_procedure->id;
            $eco_com_procedure_object->name = $eco_com_procedure->getTextName();
            $eco_com_procedure_list->push($eco_com_procedure_object);
        }
        return response()->json([
            'error' => false,
            'message' => 'Listado de movimientos de complementos economicos',
            'payload' => [
                'eco_com_procedures' => $eco_com_procedure_list
            ]
        ]);
    }
public function listProcedures(Request $request)
{
    $identity_card = mb_strtoupper($request->identity_card);
    $birth_date = Carbon::parse($request->birth_date)->format('Y-m-d');

    $eco_com_beneficiaries = EcoComBeneficiary::with([
        'economic_complement.eco_com_procedure',
        'economic_complement.eco_com_modality',
        'economic_complement.eco_com_reception_type',
        'economic_complement.eco_com_state',
        'economic_complement.observations',
        'economic_complement.eco_com_beneficiary'
    ])
    ->where('identity_card', $identity_card)
    ->where('birth_date', $birth_date)
    ->orderByDesc('created_at')
    ->get();

    if ($eco_com_beneficiaries->isEmpty()) {
        return response()->json([
            'error' => true,
            'message' => 'Persona no registrada, favor revisar la información.',
            'data' => (object)[]
        ]);
    }
    $data = $eco_com_beneficiaries->map(function ($beneficiary) {
        $eco_com = $beneficiary->economic_complement;
        $observations = $eco_com->observations()->where('enabled', false)->pluck('shortened')->unique();

        return [
            "id" => $eco_com->id,
            "title" => mb_strtoupper($eco_com->eco_com_procedure->semester) . ' SEMESTRE ' . Carbon::parse($eco_com->eco_com_procedure->year)->year,
            "beneficiario" => $eco_com->eco_com_beneficiary->full_name,
            "ci" => $eco_com->eco_com_beneficiary->ciWithExt(),
            "semestre" => $eco_com->eco_com_procedure->fullName(),
            "fecha_de_recepcion" => $eco_com->reception_date ? Carbon::parse($eco_com->reception_date) : null,
            "nro_tramite" => $eco_com->code,
            "tipo_de_prestacion" => $eco_com->eco_com_modality->shortened ?? '',
            "tipo_de_tramite" => $eco_com->eco_com_reception_type->name ?? '',
            "estado" => $eco_com->eco_com_state->name ?? '',
            "observaciones_del_tramite" => $observations->isNotEmpty() ? $observations->values() : 'Ninguna',
        ];
    });

    return response()->json([
        'error' => false,
        'message' => 'Trámites',
        'data' => $data,
    ]);
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
}
