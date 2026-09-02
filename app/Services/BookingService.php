<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function book(array $data): Collection|Enrollment
    {
        return DB::transaction(function () use ($data) {
            if ($data['enrollment_type'] === 'regular') {
                return $this->bookRegularModules($data);
            }

            return $this->bookMakeup($data);
        });
    }

    /**
     * Réservation d'un nouveau module.
     */
    protected function bookRegularModules(array $data): Collection
    {
        /** @var Lesson $startingLesson */
        $startingLesson = Lesson::with('course.type')
            ->lockForUpdate()
            ->findOrFail($data['lesson_id']);

        $typeName = strtolower(
            $startingLesson->course->type->name ?? ''
        );

        $isCollective =
            str_contains($typeName, 'collectif') ||
            (int) $startingLesson->course->type_id === 1;

        /*
         * Détermination du nombre de séances.
         */
        if ($isCollective) {
            $totalLessons = (int) ($data['total_lessons'] ?? 10);
        } else {
            $totalLessons = Lesson::query()
                ->where('course_id', $startingLesson->course_id)
                ->where('date', '>=', $startingLesson->date)
                ->where('is_cancelled', false)
                ->count();
        }

        $participants = $data['participants'] ?? [];

        if (empty($participants)) {
            throw ValidationException::withMessages([
                'participants' =>
                    'Au moins un participant doit être sélectionné pour ce module.',
            ]);
        }

        /*
         * Séances qui composeront le nouveau module.
         */
        $sequenceLessons = Lesson::query()
            ->where('course_id', $startingLesson->course_id)
            ->where('date', '>=', $startingLesson->date)
            ->where('is_cancelled', false)
            ->orderBy('date', 'asc')
            ->take($totalLessons)
            ->lockForUpdate()
            ->get();

        if ($sequenceLessons->count() < $totalLessons) {
            throw ValidationException::withMessages([
                'total_lessons' =>
                    "Impossible de réserver : seules {$sequenceLessons->count()} séance(s) sont disponibles sur ce cours.",
            ]);
        }

        $sequenceLessonIds = $sequenceLessons->pluck('id');

        /*
         * Prix du module par participant.
         */
        $pricePerParticipant = $isCollective
            ? $startingLesson->effective_price * $totalLessons
            : (
                (float) $startingLesson->course->default_price
                    ?: $startingLesson->effective_price * $totalLessons
            );

        $createdModules = collect();

        foreach ($participants as $index => $participantData) {
            $participantType = $participantData['participant_type'];
            $participantId = (int) $participantData['participant_id'];

            /*
             * IMPORTANT :
             *
             * Un participant déjà inscrit OU noté absent sur une des
             * séances du futur module ne peut pas acheter un nouveau
             * module qui recouvre cette séance.
             *
             * "absent" signifie que son inscription existe toujours :
             * il a seulement libéré temporairement sa place.
             */
            $conflictingEnrollment = Enrollment::query()
                ->whereIn('lesson_id', $sequenceLessonIds)
                ->whereIn('status', ['registered', 'absent'])
                ->whereHas('module', function ($query) use (
                    $participantType,
                    $participantId
                ) {
                    $query
                        ->where('participant_type', $participantType)
                        ->where('participant_id', $participantId);
                })
                ->first();

            if ($conflictingEnrollment) {
                $message = $conflictingEnrollment->status === 'absent'
                    ? "Ce participant est déjà noté absent sur une séance comprise dans ce module. Annulez d'abord l'avis d'absence depuis votre espace membre si vous souhaitez reprendre cette place."
                    : "Ce participant est déjà inscrit à une séance comprise dans ce module.";

                throw ValidationException::withMessages([
                    "participants.{$index}.participant_id" => $message,
                ]);
            }

            /*
             * Seulement après les contrôles, on crée le module.
             */
            $module = Module::create([
                'participant_id' => $participantId,
                'participant_type' => $participantType,
                'type_id' => $startingLesson->course->type_id,
                'total_lessons' => $totalLessons,
                'attended_lessons' => 0,
                'paid_price' => $pricePerParticipant,
                'purchase_date' => now(),
                'is_active' => true,
            ]);

            /*
             * Création des inscriptions régulières du module.
             */
            foreach ($sequenceLessons as $lesson) {
                Enrollment::create([
                    'module_id' => $module->id,
                    'lesson_id' => $lesson->id,
                    'status' => 'registered',
                    'enrollment_type' => 'regular',
                    'spot_type' => $participantData['spot_type'],
                ]);
            }

            $createdModules->push($module);
        }

        return $createdModules;
    }

    /**
     * Réservation d'un rattrapage.
     */
    protected function bookMakeup(array $data): Enrollment
    {
        /*
         * 1. Verrouillage de la séance cible.
         */
        /** @var Lesson $targetLesson */
        $targetLesson = Lesson::with('course.type')
            ->lockForUpdate()
            ->findOrFail($data['lesson_id']);

        /*
         * 2. Verrouillage du crédit d'absence.
         */
        /** @var Absence $absence */
        $absence = Absence::with('enrollment.module')
            ->lockForUpdate()
            ->findOrFail($data['absence_id']);

        if (! $absence->isAvailableForMakeup()) {
            throw ValidationException::withMessages([
                'absence_id' =>
                    "Ce crédit d'absence n'est plus disponible.",
            ]);
        }

        $module = $absence->enrollment->module;

        $participantType = $module->participant_type;
        $participantId = (int) $module->participant_id;

        /*
         * 3. Le participant ne peut pas utiliser un rattrapage
         *    sur une séance où il est déjà inscrit ou absent.
         */
        $conflictingEnrollment = Enrollment::query()
            ->where('lesson_id', $targetLesson->id)
            ->whereIn('status', ['registered', 'absent'])
            ->whereHas('module', function ($query) use (
                $participantType,
                $participantId
            ) {
                $query
                    ->where('participant_type', $participantType)
                    ->where('participant_id', $participantId);
            })
            ->first();

        if ($conflictingEnrollment) {
            $message = $conflictingEnrollment->status === 'absent'
                ? "Ce participant est déjà noté absent sur cette séance. Annulez d'abord l'avis d'absence si vous souhaitez reprendre sa place d'origine."
                : "Ce participant est déjà inscrit à cette séance.";

            throw ValidationException::withMessages([
                'absence_id' => $message,
            ]);
        }

        /*
         * 4. Sécurité supplémentaire :
         *    ce module ne peut pas déjà avoir une inscription
         *    sur cette séance.
         */
        $moduleEnrollmentExists = Enrollment::query()
            ->where('lesson_id', $targetLesson->id)
            ->where('module_id', $module->id)
            ->exists();

        if ($moduleEnrollmentExists) {
            throw ValidationException::withMessages([
                'absence_id' =>
                    'Une inscription pour ce module existe déjà sur cette séance.',
            ]);
        }

        /*
         * 5. Création du rattrapage.
         */
        $enrollment = Enrollment::create([
            'module_id' => $module->id,
            'lesson_id' => $targetLesson->id,
            'status' => 'registered',
            'enrollment_type' => 'makeup',
            'spot_type' => $data['spot_type'],
            'replaces_absence_id' => $absence->id,
        ]);

        /*
         * 6. Le crédit est immédiatement consommé.
         */
        $absence->deactivate();

        return $enrollment;
    }
}
