<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class NotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function messages(){
         
        return [
            'action.required' => 'La acción a realizar es requerida',
            'title.required' => 'El título es requerido para la notiticación',
            'message.required' => 'El mensaje es requerido para la notificación',
            'user_id.required' => 'Es id del usuario es requerido'
        ];
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'action'         => [
                'required',
                'string',
                function($attribute, $value, $fail) {
                    if(!in_array($value, ['economic_complement_payment', 'observations', 'receipt_of_requirements']))
                    $fail('El valor del campo '.$attribute.' es incorrecto');
                }
            ],
            'payment_method' => [             
                'exclude_if:action,receipt_of_requirements,observations',
                'required_if:action,economic_complement_payment',
                'required',
                'numeric',
                function($attribute, $value, $fail) {
                    if(!in_array($value, [0, 24, 25, 29])) $fail('El '. $attribute. ' (método de pago) es incorrecto ');
                }
            ],
            'modality' => [
                'exclude_if:action,observations,receipt_of_requirements',
                'exclude_if:payment_method,0',
                'required',
                'numeric',
                function($attribute, $value, $fail){
                    if(!in_array($value, [29, 30, 31])) $fail('El '. $attribute. ' (modalidad) es incorrecto ');
                }
            ],
            'type_observation' => 'required_if:action,observations|numeric',
            'hierarchies'      => 'numeric',
            'year'             => 'required_if:action,observations',
            'semester'         => 'required_if:action,observations|string',
            'title'            => 'required|string',
            'message'          => 'required|string',
            'attached'         => 'required|string',
            'user_id'          => 'required|numeric'
        ];
    }
}
