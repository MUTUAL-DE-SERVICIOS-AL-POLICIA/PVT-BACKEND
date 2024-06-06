<?php

namespace App\Http\Controllers\EconomicComplement;

use App\Http\Controllers\Controller;
use App\Models\EconomicComplement\EcoComProcedure;
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
            $eco_com_procedure_object->name = $eco_com_procedure->semester . " SEMESTRE " . $eco_com_procedure->year;
            $eco_com_procedure_list->push($eco_com_procedure_object);
        }
        return response()->json([
            'error' => "false",
            'message' => 'Listado de movimientos de complementos economicos',
            'payload' => [
                'eco_com_procedures' => $eco_com_procedure_list
            ]
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
