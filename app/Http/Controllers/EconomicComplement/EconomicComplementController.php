<?php

namespace App\Http\Controllers\EconomicComplement;

use App\Http\Controllers\Controller;
use App\Models\EconomicComplement\EconomicComplement;
use Illuminate\Http\Request;
use stdClass;

class EconomicComplementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    public function ListEconomicComplement(Request $request, $affiliate_id )
    {
        $request["affiliate_id"] = $affiliate_id;
        $list_economic_complement = EconomicComplement::where("affiliate_id",$affiliate_id)->get();
        if (!$list_economic_complement->isEmpty()) {
            $procedures= collect();
            foreach ($list_economic_complement as $economic_complement) {
                $economic_complement_object = new \stdClass();
                $economic_complement_object->id = $economic_complement->eco_com_procedure->id;
                $economic_complement_object->name = $economic_complement->eco_com_procedure->getTextName();
                $procedures->push($economic_complement_object);
            }
            return response()->json([
                    'error' => false,
                    'message' => 'Detalle de deudas',
                    'payload' => [
                        "eco_com_procedures" => $procedures
                    ]
            ]);
        }
    else{
        return response()->json([

                'error' => true,
                'message' => 'El afiliado no tiene complementos pagados',
                'payload' => [
                    "eco_com_procedures"=>[]
                ]

        ]);
    }


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
