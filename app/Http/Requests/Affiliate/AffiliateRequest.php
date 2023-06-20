<?php

namespace App\Http\Requests\Affiliate;

use BinaryCats\Sanitizer\Laravel\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

class AffiliateRequest extends FormRequest
{
    use SanitizesInput;
    
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'affiliate_state_id' => 'nullable|exists:affiliate_states,id',
            'gender' => 'in:M,F',
            'birth_date' => 'date_format:"Y-m-d"',
            'civil_status' => 'in:C,D,S,V',
            'degree_id' => 'nullable|exists:degrees,id',
            'identity_card' => 'alpha_dash|min:5|max:15',
            'first_name' => 'alpha_spaces|min:3',
            'second_name' => 'nullable|alpha_spaces|min:3',
            'last_name' => 'sometimes|required_without:mothers_last_name|nullable|alpha_spaces|min:3',
            'mothers_last_name' => 'sometimes|required_without:last_name|nullable|alpha_spaces|min:3',
            'surname_husband' => 'nullable|alpha_spaces|min:3',
            'city_birth_id' => 'exists:cities,id',
            'city_identity_card_id' => 'nullable|exists:cities,id',
            'pension_entity_id' => 'nullable|exists:pension_entities,id',
            'cell_phone_number' => 'nullable|array',
            'date_death' => 'nullable|date_format:"Y-m-d"',
            'date_entry' => 'nullable|date_format:"Y-m-d"',
            'date_derelict' => 'nullable|date_format:"Y-m-d"',
            'due_date' => 'nullable|date_format:"Y-m-d"',
            'financial_entity_id' => 'nullable|exists:financial_entities,id',
            'sigep_status' => 'nullable|alpha_spaces|min:3|in:ACTIVO,ELABORADO,VALIDADO,SIN REGISTRO',
            'account_number' => 'nullable|integer',
            'service_years' => 'nullable|integer|min:0',
            'service_months' => 'nullable|integer|min:0|max:11',
            'unit_police_description' => 'nullable|min:3'
        ];
        switch ($this->method()) {
            case 'POST': {
                    foreach (array_slice($rules, 0, 6) as $key => $rule) {
                        $rules[$key] = implode('|', ['required', $rule]);
                    }
                    $rules['identity_card'] = implode('|', ['unique:affiliates', $rules['identity_card']]);
                    $rules['last_name'] = implode('|', ['required_without:mothers_last_name', $rules['last_name']]);
                    $rules['mothers_last_name'] = implode('|', ['required_without:last_name', $rules['mothers_last_name']]);
                    return $rules;
                }
            case 'PUT':
            case 'PATCH': {
                    return $rules;
                }
        }
        return $rules;
    }
    protected function prepareForValidation()
    {
        if (isset($this->first_name)) {
            $this->merge([
                'first_name' => trim(mb_strtoupper($this->first_name)),
            ]);
        }
        if (isset($this->second_name)) {
            $this->merge([
                'second_name' => trim(mb_strtoupper($this->second_name)),
            ]);
        }
        if (isset($this->last_name)) {
            $this->merge([
                'last_name' => trim(mb_strtoupper($this->last_name)),
            ]);
        }
        if (isset($this->mothers_last_name)) {
            $this->merge([
                'mothers_last_name' => trim(mb_strtoupper($this->mothers_last_name)),
            ]);
        }
        if (isset($this->reason_death)) {
            $this->merge([
                'reason_death' => trim(mb_strtoupper($this->reason_death)),
            ]);
        }
        if (isset($this->identity_card)) {
            $this->merge([
                'identity_card' => trim(mb_strtoupper($this->identity_card)),
            ]);
        }
        if (isset($this->surname_husband)) {
            $this->merge([
                'surname_husband' => trim(mb_strtoupper($this->surname_husband)),
            ]);
        }
        if (isset($this->gender)) {
            $this->merge([
                'gender' => trim(mb_strtoupper($this->gender)),
            ]);
        }
        if (isset($this->civil_status)) {
            $this->merge([
                'civil_status' => trim(mb_strtoupper($this->civil_status)),
            ]);
        }
        if (isset($this->sigep_status)) {
            $this->merge([
                'sigep_status' => trim(mb_strtoupper($this->sigep_status)),
            ]);
        }
        if (isset($this->unit_police_description)) {
            $this->merge([
                'unit_police_description' => trim(mb_strtoupper($this->unit_police_description)),
            ]);
        }
    }

    public function messages()
    {
        return [
        ];
    }
}
