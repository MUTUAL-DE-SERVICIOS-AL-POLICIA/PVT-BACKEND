<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use App\Models\Admin\Module;
use App\Models\Admin\Role;
use App\Models\Admin\RoleSequence;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanBorrower;
use App\Models\Loan\LoanPayment;
use App\Models\Loan\LoanState;
use App\Models\Procedure\ProcedureModality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
class LoanController extends Controller
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
    public function print_plan(Request $request, Loan $loan)
    {
        if($loan->disbursement_date){
            $procedure_modality = $loan->modality;
            $file_title = implode('_', ['PLAN','DE','PAGOS', $procedure_modality->shortened, $loan->code,Carbon::now()->format('m/d')]);
            $is_dead = false;
            if($loan->borrower->first()->type == 'spouses')
                $is_dead = true;
            $data = [
                'header' => [
                    'direction' => 'DIRECCIÓN DE ESTRATEGIAS SOCIALES E INVERSIONES',
                    'unity' => 'UNIDAD DE INVERSIÓN EN PRÉSTAMOS',
                    'table' => [
                        ['Tipo', $loan->modality->procedure_type->second_name],
                        ['Modalidad', $loan->modality->shortened],
                        ['Fecha', Carbon::now()->format('d/m/Y')],
                        ['Hora', Carbon::now()->format('H:i')],
                    ]
                ],
                'title' => 'PLAN DE PAGOS',
                'loan' => $loan,
                'lender' => $is_dead ? $loan->affiliate->spouse : $loan->affiliate,
                'is_dead'=> $is_dead,
                'file_title'=>$file_title
            ];
            $file_name = implode('_', ['plan', $procedure_modality->shortened, $loan->code]) . '.pdf';
            $pdf=PDF::loadView('loan/payment_plan', $data);
            return $pdf->stream();
        }else{
            return "Prestamo no desembolsado";
        }
    }
    public function print_kardex(Request $request, Loan $loan, $standalone = true)
    {
        if($loan->disbursement_date){
            $procedure_modality = $loan->modality;
            $is_dead = false;
            if($loan->borrower->first()->type == 'spouses'){
                $is_dead = true;
            }
                $file_title = $procedure_modality->shortened;
                $data = [
                    'header' => [
                        'direction' => 'DIRECCIÓN DE ESTRATEGIAS SOCIALES E INVERSIONES',
                        'unity' => 'UNIDAD DE INVERSIÓN EN PRÉSTAMOS',
                        'table' => [
                            ['Tipo', $loan->modality->procedure_type->second_name],
                            ['Modalidad', $loan->modality->shortened],
                            ['Fecha', Carbon::now()->format('d/m/Y')],
                            ['Hora', Carbon::now()->format('H:i')],
                        ]
                    ],
                    'title' => 'KARDEX DE PAGOS',
                    'loan' => $loan,
                    'lender' => $is_dead ? $loan->affiliate->spouse : $loan->affiliate,
                    'file_title' => $file_title,
                    'is_dead' => $is_dead
                ];
                $pdf=PDF::loadView('loan.payment_kardex', $data);
                return $pdf->setPaper('a4', 'landscape')->stream();
            }else{
                return "prestamo no desembolsado";
            }
    }
     /**
     * @OA\Get(
     *     path="/api/app/get_information_loan/{id_affiliate}",
     *     tags={"OFICINA VIRTUAL"},
     *     summary="LISTADO DE PRESTAMOS DE UN AFILIADO",
     *     operationId="get_information_loan",
     * @OA\Parameter(
     *         name="id_affiliate",
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
     * )
     *
     * Get status of virtual office.
     *
     * @param Request $request
     * @return void
     */

    public static function get_workflow($idloan){
        $procedure=Loan::find($idloan)->modality->procedure_type->id;
        $flows=RoleSequence::where('procedure_type_id',$procedure)->get();
        $location=Loan::find($idloan)->role->display_name;
        $areas=[];
        foreach($flows as $flow){
            $name=$flow->current_role->display_name;
            array_push($areas,array(
                "display_name"=> $name,
                "state"=> $location==$name?? true
                )
            );
        }
        $last=RoleSequence::where('procedure_type_id',$procedure)->orderby('sequence_number_flow','desc')->first()->next_role_id;
        $roleName=Role::find($last)->display_name;
        array_push($areas,array(
            "display_name"=> $roleName,
                "state"=> $location==$roleName?? true
                )
        );
        return $areas;
    }
    public static function  get_percentaje_loan(Loan $loan){
        $amount=$loan->amount_approved;
        $balance=$loan->balance;
        $percentage=(100*($amount-$balance))/$amount;
        return $percentage;
    }
    public function get_information_loan(Request $request, $idAffiliate)
    {
        $request['affiliate_id'] = $idAffiliate;
        $hasLoans = DB::table('loans')->where('affiliate_id',$request->id_affiliate)->exists();
        if ($hasLoans) {
            $loans = Loan::where([['affiliate_id', '=',$request->id_affiliate]])->whereIn('state_id',[1,3,4])->get();
        $current=[];
        $inProcess=[];
        $liquidated=[];
        foreach ($loans as $loan ) {
            switch ($loan->state_id) {
                case 1:
                $state = LoanState::find($loan->state_id);
                $procedure = ProcedureModality::find($loan->procedure_modality_id);
                $type = $loan->modality->procedure_type->name;
                $role = Role::find($loan->role_id);
                $flow=$this->get_workflow($loan->id);
                $loan->state_name = $state->name;
                $loan->procedure_modality_name = $procedure->name;
                array_push($inProcess,array(
                    'code' => $loan->code,
                    'procedure_modality_name' => $procedure->name,
                    'procedure_type_name' => $type,
                    'location' => $role->display_name,
                    'validated' => $loan->validated,
                    'state_name' => $loan->state_name,
                    'flow'=> $flow
                )
                );
                    break;
                case 3:
                    array_push($current,array(
                        "id"=> $loan->id,
                        "code"=> $loan->code,
                        "procedure_modality" => $loan->modality->name,
                        "request_date"=> $loan->disbursement_date,
                        "amount_requested"=> $loan->amount_requested,
                        "city"=> $loan->city->name,
                        "interest"=> $loan->interest->annual_interest,
                        "state"=> $loan->state->name,
                        "amount_approved"=> $loan->amount_approved,
                        "liquid_qualification_calculated"=> $loan->liquid_qualification_calculated,
                        "loan_term"=> $loan->loan_term,
                        "refinancing_balance"=> $loan->refinancing_balance,
                        "payment_type"=> $loan->payment_type->name,
                        "destiny_id"=> $loan->destiny->name,
                        "quota"=> $loan->EstimatedQuota,
                        "percentage_paid"=>$this->get_percentaje_loan($loan)
                        )
                    );
                    break;
                case 4:
                    array_push($liquidated,array(
                        "id"=> $loan->id,
                        "code"=> $loan->code,
                        "procedure_modality" => $loan->modality->name,
                        "request_date"=> $loan->disbursement_date,
                        "amount_requested"=> $loan->amount_requested,
                        "city"=> $loan->city->name,
                        "interest"=> $loan->interest->annual_interest,
                        "state"=> $loan->state->name,
                        "amount_approved"=> $loan->amount_approved,
                        "liquid_qualification_calculated"=> $loan->liquid_qualification_calculated,
                        "loan_term"=> $loan->loan_term,
                        "refinancing_balance"=> $loan->refinancing_balance,
                        "payment_type"=> $loan->payment_type->name,
                        "destiny_id"=> $loan->destiny->name,
                        "quota"=> $loan->EstimatedQuota,
                        "percentage_paid"=>$this->get_percentaje_loan($loan)
                        )
                    );
                    break;
                default:
                    break;
            }
        }
        return response()->json([
            'error' => 'false',
            'message' => 'Lista de Prestamos',
            'payload' => [
                'inProcess'=> $inProcess,
                'current' => $current,
                'liquited' => $liquidated,
            ],
        ]);
        }
        else{
            return response()->json([
                'error' => 'true',
                'message' => 'El afiliado no tiene prestamos',
                'payload' => [
                    'inProcess'=> [],
                    'current' => [],
                    'liquited' => [],
                ],
            ]);
        }
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
