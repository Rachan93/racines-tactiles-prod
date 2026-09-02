<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserMailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'channels' => 'required|array|in:mail,notification',
            'channels.*' => 'in:mail,notification',
            'recipients' => 'required',
            'template' => 'required|string|max:20',
            'subject' => 'required_if:template,custom|nullable|string|max:255',
            'message' => 'required_if:template,custom|nullable|string|max:10000',
        ];
    }
}
