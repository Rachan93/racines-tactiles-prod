<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class TestBladePostStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        Log::info('🔍 authorize() appelé');
        return true;
    }

    public function rules(): array
    {
        Log::info('📏 rules() appelé');
        return [
            'nomPost' => ['required', 'string', 'max:30'],
            'prenomPost' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function prepareForValidation()
    {
        Log::info('🧪 prepareForValidation() appelé', $this->all());
    }
    protected $validatedData = [];

    public function withValidator($validator)
    {
        // Logguer l'état
        Log::info('Validation status:', [
            'errors' => $validator->errors()->all(),
            'validated' => $validator->valid(),
        ]);

        // Stocker les données validées seulement si la validation réussit

            $this->validatedData = $validator->valid();

    }

    public function failedValidation(Validator $validator)
    {
        Log::error('💥 Échec de validation', [
            'input' => $this->all(),
            'errors' => $validator->errors()->all(),
        ]);
        // dd('valid', $this->validatedData);
        throw new HttpResponseException(
            response()->json([
                'status' => 'fail',
                'errors' => $validator->errors(),
                'validated' => $this->validatedData,


            ], 422)
        );
    }
}
