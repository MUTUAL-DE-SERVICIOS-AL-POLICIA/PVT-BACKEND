<?php

namespace App\Http\Controllers;

use App\Models\Admin\Module;
use App\Models\Admin\Role;
use App\Models\Admin\RoleSequence;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanBorrower;
use App\Models\Loan\LoanState;
use App\Models\Procedure\ProcedureModality;
use App\Models\Procedure\ProcedureState;
use App\Models\QuotaAidMortuary\QuotaAidBeneficiary;
use App\Models\QuotaAidMortuary\QuotaAidMortuary;
use App\Models\RetirementFund\RetFunBeneficiary;
use App\Models\RetirementFund\RetFunState;
use App\Models\RetirementFund\RetirementFund;
use App\Models\Workflow\WfState;
use Illuminate\Http\Request;

class ProcedureQRController extends Controller
{
    public static function get_porcentage_loan($id,$state){
        if ($state!=90) {
            $flow=RoleSequence::Where('procedure_type_id',$id)->where('role_id',$state)->first();
            $number_flows = count(RoleSequence::where('procedure_type_id',$id)->get())+1;
            $porcentage=(100*$flow->sequence_number_flow)/$number_flows;
        }
        else {
            $porcentage=100;
        }
        return $porcentage;
    }
 /**
     * @OA\Get(
     *     path="/api/global/procedure_qr/{module_id}/{uuid}",
     *     tags={"TRÁMITES"},
     *     summary="TRÁMITE DE ACUERDO AL MÓDULO Y UUID SOLICITADO",
     *     operationId="getProcedureQRByModuleAndUuid",
     *     @OA\Parameter(
     *         name="module_id",
     *         in="path",
     *         description="",
     *         example=6,
     *
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format = "int64"
     *         )
     *       ),
     *      @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="",
     *         example="cc2f6a58-9ea8-46f3-94df-c3e61aa3bbcc",
     *         required=true,
     *       ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *            type="object"
     *         )
     *     ),
     * )
     *
     * getProcedureQRB
     *
     * @param Request $request
     * @return void
     */

    public function procedure_qr(Request $request,$module_id,$uuid)
    {
        $request['module_id'] = $module_id;
        $request['uuid'] = $uuid;
        $request->validate([
            'module_id' => 'required|integer|exists:modules,id'
        ]);

        switch ($module_id) {
            case 6:
                $request->validate([
                    'uuid' => 'required|uuid|exists:loans,uuid'
                ]);
                $person = collect();
                $module = Module::find($module_id);
                $data = Loan::where('uuid',$uuid)->first();
                $state = LoanState::find($data->state_id);
                $procedure = ProcedureModality::find($data->procedure_modality_id);
                $type = Loan::find($data->id)->modality->procedure_type->name;
                $title = "Prestatario(a)";

                $borrower = LoanBorrower::where('loan_id',$data->id)->first();

                    $person->push([
                        'full_name' => $borrower->fullName,
                        'identity_card' => $borrower->identity_card,
                    ]);

                $role = Role::find($data->role_id);
                $RoleSeq=Loan::find($data->id)->modality->procedure_type->id;
                $data->module_display_name = $module->display_name;
                $data->state_name = $state->name;
                $data->procedure_modality_name = $procedure->name;
                $data->procedure_type_name = $type;
                $data->title = $title;
                $data->person = $person;
                $data->location =$role->display_name;
                $data->porcentage= $this->get_porcentage_loan($RoleSeq,$role->id);
                break;

            case 4:
                $request->validate([
                    'uuid' => 'required|uuid|exists:quota_aid_mortuaries,uuid'
                ]);
                $person = collect();
                $module = Module::find($module_id);
                $data = QuotaAidMortuary::where('uuid',$uuid)->first();
                $state = ProcedureState::find($data->procedure_state_id);
                $procedure = ProcedureModality::find($data->procedure_modality_id);
                $type = QuotaAidMortuary::find($data->id)->procedure_modality->procedure_type->name;
                $title = "Beneficiario(s)";

                $beneficiaries = QuotaAidBeneficiary::where('quota_aid_mortuary_id',$data->id)->get();
                foreach($beneficiaries as $beneficiary){
                    $person->push([
                        'full_name' => $beneficiary->fullName,
                        'identity_card' => $beneficiary->identity_card,
                    ]);
                }

                $wfstate = WfState::find($data->wf_state_current_id)->role_id;
                $wfseq = WfState::find($data->wf_state_current_id)->sequence_number;
                $role = Role::find($wfstate);
                $data->module_display_name = $module->display_name;
                $data->state_name = $state->name;
                $data->procedure_modality_name = $procedure->name;
                $data->procedure_type_name = $type;
                $data->title = $title;
                $data->person = $person;
                $data->location = $role->display_name;
                $data->validated = $data->inbox_state;
                $data->porcentage = $this->getPercentage($module_id, $wfseq);
                break;

            case 3:
                $request->validate([
                    'uuid' => 'required|uuid|exists:retirement_funds,uuid'
                ]);
                $person = collect();
                $module = Module::find($module_id);
                $data = RetirementFund::where('uuid',$uuid)->first();
                $state = RetFunState::find($data->ret_fun_state_id);
                $procedure = ProcedureModality::find($data->procedure_modality_id);
                $type = RetirementFund::find($data->id)->procedure_modality->procedure_type->name;
                $title = "Beneficiario(s)";

                $beneficiaries = RetFunBeneficiary::where('retirement_fund_id',$data->id)->get();
                foreach($beneficiaries as $beneficiary){
                    $person->push([
                        'full_name' => $beneficiary->fullName,
                        'identity_card' => $beneficiary->identity_card,
                    ]);
                }
                $wfstate = WfState::find($data->wf_state_current_id)->role_id;
                $wfseq = WfState::find($data->wf_state_current_id)->sequence_number;
                $role = Role::find($wfstate);
                $data->module_display_name = $module->display_name;
                $data->state_name = $state->name;
                $data->procedure_modality_name = $procedure->name;
                $data->procedure_type_name = $type;
                $data->title = $title;
                $data->person = $person;
                $data->location = $role->display_name;
                $data->validated = $data->inbox_state;
                $data->porcentage = $this->getPercentage($module_id, $wfseq);
                break;

            default:
                return 'Trámite no encontrado';
        }

        return response()->json([
            'message' => 'Trámite encontrado',
            'payload' => [
                'module_display_name' => $data->module_display_name,
                'title' => $data->title,
                'person' => $data->person,
                'code' => $data->code,
                'procedure_modality_name' => $data->procedure_modality_name,
                'procedure_type_name' => $data->procedure_type_name,
                'location' => $data->location,
                'validated' => $data->validated,
                'state_name' => $data->state_name,
                'porcentage' => $data->porcentage
            ],
        ]);
    }

    public function getPercentage($module_id, $wfseq)
    {
        $list = WfState::where('module_id', $module_id)
            ->where('sequence_number', '<>', 0)
            ->get();
        $count = $list->count() - 1;
        $percentage = round((100 * $wfseq) / $count);
        return $percentage;
    }
}
