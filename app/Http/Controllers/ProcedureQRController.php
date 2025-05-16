<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Loan\LoanController;
use App\Models\Admin\Module;
use App\Models\Admin\Role;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanBorrower;
use App\Models\Loan\LoanState;
use App\Models\Procedure\ProcedureModality;
use App\Models\Procedure\ProcedureState;
use App\Models\QuotaAidMortuary\QuotaAidMortuary;
use App\Models\RetirementFund\RetFunState;
use App\Models\RetirementFund\RetirementFund;
use App\Models\EconomicComplement\EconomicComplement;
use App\Models\Workflow\WfState;
use Illuminate\Http\Request;

class ProcedureQRController extends Controller
{
    public static function get_porcentage_loan($workflow, $current_state_id)
    {
        $c = 1;
        $sequences = $workflow->get_sequence();
        foreach ($sequences as $sequence) {
            if ($sequence->wf_state_current_id == $current_state_id)
                break;
            $c++;
        }
        $porcentage = round((100 * (($c) / $sequences->count())),2);
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

    public function procedure_qr(Request $request, $module_id, $uuid)
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
                $loan = Loan::where('uuid', $uuid)->first();

                $module = Module::find($module_id);
                $data = $loan;
                $state = $loan->state;
                $procedure = $loan->modality;
                $type = Loan::find($data->id)->modality->procedure_type->name;
                $title = "Prestatario(a)";
                $borrower = $loan->borrower->first();
                $person->push([
                    'full_name' => $borrower->fullName,
                    'identity_card' => $borrower->identity_card,
                ]);
                $wf_state = $loan->current_state;
                $workflowSequence = $loan->modality->workflow;
                $data->module_display_name = $module->display_name;
                $data->state_name = $state->name;
                $data->procedure_modality_name = $procedure->name;
                $data->procedure_type_name = $type;
                $data->title = $title;
                $data->person = $person;
                $data->location = $wf_state->name;
                $data->porcentage = $this->get_porcentage_loan($workflowSequence, $wf_state->id);
                $data->flow = LoanController::get_workflow($loan);
                $data->observations = null;
                $data->observations_title = null;
                break;

            case 4:
                $request->validate([
                    'uuid' => 'required|uuid|exists:quota_aid_mortuaries,uuid'
                ]);
                $person = collect();
                $module = Module::find($module_id);
                $data = QuotaAidMortuary::where('uuid', $uuid)->first();
                $state = ProcedureState::find($data->procedure_state_id);
                $procedure = ProcedureModality::find($data->procedure_modality_id);
                $type = QuotaAidMortuary::find($data->id)->procedure_modality->procedure_type->name;
                $title = "Titular";

                $person->push([
                    'full_name' => $data->affiliate->fullName. "\n" .$data->affiliate->identity_card,
                    'identity_card' => $data->affiliate->identity_card,
                ]);

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
                $data->flow = null;
                $data->observations_title = "Observaciones";
                $data->observations = $this->getObservations($data);
                break;

            case 3:
                $request->validate([
                    'uuid' => 'required|uuid|exists:retirement_funds,uuid'
                ]);
                $person = collect();
                $module = Module::find($module_id);
                $data = RetirementFund::where('uuid', $uuid)->first();
                $state = RetFunState::find($data->ret_fun_state_id);
                $procedure = ProcedureModality::find($data->procedure_modality_id);
                $type = RetirementFund::find($data->id)->procedure_modality->procedure_type->name;
                $title = "Titular";

                $person->push([
                    'full_name' => $data->affiliate->fullName. "\n" .$data->affiliate->identity_card,
                    'identity_card' => $data->affiliate->identity_card,
                ]);

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
                $data->flow = null;
                $data->observations_title = "Observaciones";
                $data->observations = $this->getObservations($data);
                break;
            case 2:
                $request->validate([
                    'uuid' => 'required|uuid|exists:economic_complements,uuid'
                ]);
                $person = collect();
                $module = Module::find($module_id);
                $data = EconomicComplement::where('uuid', $uuid)->first();
                $state = $data->eco_com_state;
                $procedure = $data->eco_com_modality;
                $type = $data->eco_com_procedure->semester;
                $title = "BENEFICIARIO";
                $person->push([
                    'full_name' => $data->eco_com_beneficiary->fullName. "\n" .$data->eco_com_beneficiary->identity_card,
                    'identity_card' => $data->eco_com_beneficiary->identity_card,
                ]);
                $wfstate = WfState::find($data->wf_current_state_id)->name;
                $wfseq = WfState::find($data->wf_current_state_id)->sequence_number;
                $data->module_display_name = $module->display_name;
                $data->state_name = $state->name;
                $data->procedure_modality_name = $procedure->shortened;
                $data->procedure_type_name = $type;
                $data->title = $title;
                $data->person = $person;
                $data->location =  $wfstate;
                $data->validated = $data->inbox_state;
                $data->porcentage = $this->getPercentage($module_id, $wfseq);
                $data->flow = null;
                $data->observations_title = null;
                $data->observations = null;
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
                'porcentage' => $data->porcentage,
                'flow' => $data->flow,
                'observations_title' => $data->observations_title,
                'observations' => $data->observations
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

    public function getObservations($data)
    {
        $list_observations = collect();
        $observations = $data->observations;
        foreach ($observations as $observation) {
            if ($observation->enabled == false)
                $list_observations->push([
                    'message' => $observation->observation_type->name.": ".$observation->message
                                 ."\nObservado por: ".$observation->user->username,
                    'enabled' => $observation->enabled,
                ]);
        }
        return $list_observations;
    }
}
