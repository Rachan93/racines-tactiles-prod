<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseUpdateRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la modification du cours parent.
     */
    public function rules(): array
    {
        return [
            // Paramètres généraux du cours
            'name' => ['required', 'string', 'max:255'],
            'default_instructor_id' => ['required', 'integer', 'exists:instructors,id'],
            'default_start_time' => ['required', 'date_format:H:i'],
            'default_end_time' => ['required', 'date_format:H:i', 'after:default_start_time'],
            'default_spots_max_wheel' => ['required', 'integer', 'min:0', 'max:50'],
            'default_spots_max_handbuilding' => ['required', 'integer', 'min:0', 'max:50'],
            'default_price' => ['required', 'numeric', 'min:0'],

            // Visibilité & Mise en avant
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],

            // Gestion des surcharges futures (globale ou sélective par IDs)
            'reset_future_overrides' => ['nullable', 'boolean'],
            'reset_lesson_ids' => ['nullable', 'array'],
            'reset_lesson_ids.*' => ['integer', 'exists:lessons,id'],

            // Contenu éditorial & bilingue (spécifique aux Stages & Masterclasses)
            'name_en' => ['nullable', 'string', 'max:255'],
            'sub_type' => ['nullable', 'string', Rule::in(['wheel', 'external', 'themed', 'one-off'])],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'practical_info' => ['nullable', 'string', 'max:10000'],
            'practical_info_en' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * Gardes-fous métier :
     * 1. Au moins 1 place au total (Tour ou Modelage).
     * 2. Interdiction de réduire les capacités en dessous des inscriptions existantes sur les séances futures impactées.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Course|null $course */
            $course = $this->route('course');

            if (! $course) {
                return;
            }

            $newWheel = (int) $this->input('default_spots_max_wheel', 0);
            $newHand = (int) $this->input('default_spots_max_handbuilding', 0);
            $resetFutureOverrides = filter_var($this->input('reset_future_overrides'), FILTER_VALIDATE_BOOLEAN);
            $resetLessonIds = (array) $this->input('reset_lesson_ids', []);

            // 1. Règle du minimum de place globale
            if (($newWheel + $newHand) <= 0) {
                $validator->errors()->add(
                    'default_spots_max_wheel',
                    'Le cours doit proposer au moins 1 place au total (Tour ou Modelage).'
                );
            }

            // 2. Vérification des effectifs inscrits sur les séances futures qui hériteront de ces capacités
            $futureLessonsQuery = $course->lessons()
                ->where('date', '>=', now()->toDateString())
                ->where('is_cancelled', false);

            // Si on ne réinitialise pas tout en masse, on cible :
            // - les séances normales (is_overridden == false)
            // - ET les séances personnalisées cochées individuellement pour réinitialisation
            if (! $resetFutureOverrides) {
                $futureLessonsQuery->where(function ($q) use ($resetLessonIds) {
                    $q->where('is_overridden', false)
                      ->when(! empty($resetLessonIds), fn ($sub) => $sub->orWhereIn('id', $resetLessonIds));
                });
            }

            $futureLessons = $futureLessonsQuery->withCount([
                'enrollments as registered_wheel_count' => fn ($q) => $q->where('status', 'registered')->where('spot_type', 'wheel'),
                'enrollments as registered_handbuilding_count' => fn ($q) => $q->where('status', 'registered')->where('spot_type', 'handbuilding'),
            ])->get();

            foreach ($futureLessons as $lesson) {
                $formattedDate = $lesson->date ? $lesson->date->format('d/m/Y') : 'inconnue';

                // Conflit sur les tours
                if ($lesson->registered_wheel_count > $newWheel) {
                    $validator->errors()->add(
                        'default_spots_max_wheel',
                        "Impossible de réduire à {$newWheel} tours : la séance du {$formattedDate} compte déjà {$lesson->registered_wheel_count} élève(s) inscrit(s) sur un tour."
                    );
                    break;
                }

                // Conflit sur le modelage
                if ($lesson->registered_handbuilding_count > $newHand) {
                    $validator->errors()->add(
                        'default_spots_max_handbuilding',
                        "Impossible de réduire à {$newHand} places modelage : la séance du {$formattedDate} compte déjà {$lesson->registered_handbuilding_count} élève(s) inscrit(s) en modelage."
                    );
                    break;
                }
            }
        });
    }

    /**
     * Noms d'attributs personnalisés en français pour les retours d'erreur Precognition.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom du cours (FR)',
            'name_en' => 'nom du cours (EN)',
            'sub_type' => 'catégorie du stage',
            'subtitle' => 'sous-titre (FR)',
            'subtitle_en' => 'sous-titre (EN)',
            'description' => 'description (FR)',
            'description_en' => 'description (EN)',
            'practical_info' => 'informations pratiques (FR)',
            'practical_info_en' => 'informations pratiques (EN)',
            'default_instructor_id' => 'professeur par défaut',
            'default_start_time' => 'heure de début',
            'default_end_time' => 'heure de fin',
            'default_spots_max_wheel' => 'places tour par défaut',
            'default_spots_max_handbuilding' => 'places modelage par défaut',
            'default_price' => 'tarif par séance',
            'is_active' => 'statut de publication',
            'is_featured' => 'mise en avant',
            'reset_future_overrides' => 'réalignement des séances futures',
            'reset_lesson_ids' => 'séances à réinitialiser',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'default_end_time.after' => 'L\'heure de fin doit être postérieure à l\'heure de début.',
            'sub_type.in' => 'La catégorie de stage sélectionnée est invalide.',
        ];
    }
}
