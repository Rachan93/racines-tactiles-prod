<?php

namespace App\Http\Requests;

use App\Models\Attendee;
use Illuminate\Foundation\Http\FormRequest;

class AttendeeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Attendee|null $attendee */
        $attendee = $this->route('attendee');

        return $this->user() !== null
            && $attendee !== null
            && $attendee->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'birthday.before' => 'La date de naissance doit être dans le passé.',
        ];
    }
}
