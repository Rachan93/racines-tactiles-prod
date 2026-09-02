<?php

namespace App\Http\Requests;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;

class TestBladeStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        Log::info('Request data before validation:', $this->all());
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
           'nom' => ['string', 'max:30'],
           'prenom' => [ 'string', 'max:30'],
        ];
    }

      /**
     * Conditions de validation personnalisées.
     */
    public function withValidator($validator)
    {

        // Logguer l'état
        Log::info('Validation status:', [
            'errors' => $validator->errors()->all(),
            'validated' => $validator->validated(),
        ]);
    }

    public function validatedCustom()
    {
        return $this->safe()->all();
    }

}

