<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;

class LessonUpdateRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette mise à jour.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la modification d'une séance.
     */
    public function rules(): array
    {
        return [
            // Interrupteur de personnalisation
            'is_overridden' => ['required', 'boolean'],

            // Annulation exceptionnelle
            'is_cancelled' => ['required', 'boolean'],
            'cancellation_reason' => ['nullable', 'string', 'max:1000', 'required_if:is_cancelled,true'],

            // Champs de surcharge (override)
            'date' => ['nullable', 'date'],
            'override_start_time' => ['nullable', 'date_format:H:i'],
            'override_end_time' => ['nullable', 'date_format:H:i', 'after:override_start_time'],
            'override_instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'override_spots_max_wheel' => ['nullable', 'integer', 'min:0', 'max:50'],
            'override_spots_max_handbuilding' => ['nullable', 'integer', 'min:0', 'max:50'],
            'override_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Verrou de sécurité pour éviter de réduire les capacités en dessous des effectifs inscrits.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Lesson|null $lesson */
            $lesson = $this->route('lesson');

            if (! $lesson) {
                return;
            }

            $isOverridden = filter_var($this->input('is_overridden'), FILTER_VALIDATE_BOOLEAN);

            if ($isOverridden) {
                // 1. Nombre réel d'élèves actuellement inscrits sur cette séance
                $registeredWheelCount = $lesson->enrollments()
                    ->where('status', 'registered')
                    ->where('spot_type', 'wheel')
                    ->count();

                $registeredHandbuildingCount = $lesson->enrollments()
                    ->where('status', 'registered')
                    ->where('spot_type', 'handbuilding')
                    ->count();

                // 2. Vérification des places de Tour
                if ($this->has('override_spots_max_wheel') && $this->input('override_spots_max_wheel') !== null) {
                    $newWheelSpots = (int) $this->input('override_spots_max_wheel');
                    if ($newWheelSpots < $registeredWheelCount) {
                        $validator->errors()->add(
                            'override_spots_max_wheel',
                            "Impossible de réduire à {$newWheelSpots} tour(s) : il y a déjà {$registeredWheelCount} élève(s) inscrit(s) sur un tour pour cette séance."
                        );
                    }
                }

                // 3. Vérification des places de Modelage
                if ($this->has('override_spots_max_handbuilding') && $this->input('override_spots_max_handbuilding') !== null) {
                    $newHandSpots = (int) $this->input('override_spots_max_handbuilding');
                    if ($newHandSpots < $registeredHandbuildingCount) {
                        $validator->errors()->add(
                            'override_spots_max_handbuilding',
                            "Impossible de réduire à {$newHandSpots} place(s) modelage : il y a déjà {$registeredHandbuildingCount} élève(s) inscrit(s) en modelage pour cette séance."
                        );
                    }
                }

                // 4. Vérification qu'il reste au moins 1 place au total si non annulé
                $targetWheel = $this->input('override_spots_max_wheel') !== null
                    ? (int) $this->input('override_spots_max_wheel')
                    : $lesson->effective_spots_max_wheel;

                $targetHand = $this->input('override_spots_max_handbuilding') !== null
                    ? (int) $this->input('override_spots_max_handbuilding')
                    : $lesson->effective_spots_max_handbuilding;

                if (! $this->input('is_cancelled') && ($targetWheel + $targetHand <= 0)) {
                    $validator->errors()->add(
                        'override_spots_max_wheel',
                        'Une séance non annulée doit comporter au moins 1 place disponible au total.'
                    );
                }
            }
        });
    }

    /**
     * Noms d'attributs clairs en français.
     */
    public function attributes(): array
    {
        return [
            'is_overridden' => 'interrupteur de personnalisation',
            'is_cancelled' => 'interrupteur d\'annulation',
            'cancellation_reason' => 'motif d\'annulation',
            'date' => 'date de la séance',
            'override_start_time' => 'heure de début',
            'override_end_time' => 'heure de fin',
            'override_instructor_id' => 'professeur remplaçant',
            'override_spots_max_wheel' => 'places tour',
            'override_spots_max_handbuilding' => 'places modelage',
            'override_price' => 'tarif exceptionnel',
        ];
    }

    /**
     * Messages personnalisés.
     */
    public function messages(): array
    {
        return [
            'cancellation_reason.required_if' => 'Veuillez renseigner un motif explicatif pour l\'annulation de cette séance.',
            'override_end_time.after' => 'L\'heure de fin doit être postérieure à l\'heure de début.',
        ];
    }
}
