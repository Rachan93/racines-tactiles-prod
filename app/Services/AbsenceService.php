<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Attendee;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsenceService
{
    /**
     * Déclare une absence pour une inscription donnée.
     *
     * @throws ValidationException
     */
    public function declareAbsence(Enrollment $enrollment, User $user): ?Absence
    {
        return DB::transaction(function () use ($enrollment, $user) {
            /** @var Enrollment $lockedEnrollment */
            $lockedEnrollment = Enrollment::query()
                ->with(['module.participant', 'lesson'])
                ->lockForUpdate()
                ->findOrFail($enrollment->id);

            // 1. Vérification de propriété
            $this->ensureUserOwnsEnrollment($lockedEnrollment, $user);

            // 2. Vérification du statut actuel
            if (! $lockedEnrollment->isRegistered()) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Cette inscription ne peut pas être marquée absente (statut actuel : '.$lockedEnrollment->status.').',
                ]);
            }

            // 3. Vérification temporelle
            $this->ensureLessonIsUpcoming($lockedEnrollment->lesson);

            // 4. RÈGLE ANTI-POUPÉE RUSSE :
            // Si c'est un rattrapage, on passe en 'absent' mais SANS générer de ticket d'absence.
            if ($lockedEnrollment->isMakeup()) {
                $lockedEnrollment->update(['status' => 'absent']);

                return null;
            }

            // 5. Gestion du quota de rattrapages sur le module
            $module = $lockedEnrollment->module;
            $activeAbsencesCount = Absence::query()
                ->whereHas('enrollment', fn ($q) => $q->where('module_id', $module->id))
                ->where('active', true)
                ->whereNull('cancellation_date')
                ->count();

            $hasRemainingQuota = $activeAbsencesCount < $module->max_makeups_allowed;

            // 6. Passage de l'inscription en 'absent'
            $lockedEnrollment->update(['status' => 'absent']);

            // 7. Création du ticket d'absence
            return Absence::create([
                'enrollment_id' => $lockedEnrollment->id,
                'active' => $hasRemainingQuota,
                'notification_date' => now(),
            ]);
        });
    }

    /**
     * Annule une déclaration d'absence pour récupérer sa place.
     *
     * @throws ValidationException
     */
    public function cancelAbsence(Absence $absence, User $user): void
    {
        DB::transaction(function () use ($absence, $user) {
            /** @var Absence $lockedAbsence */
            $lockedAbsence = Absence::query()
                ->with(['enrollment.module.participant'])
                ->lockForUpdate()
                ->findOrFail($absence->id);

            $enrollment = $lockedAbsence->enrollment;

            // Verrouillage de la leçon pour empêcher toute réservation concurrente en parallèle
            /** @var Lesson $lesson */
            $lesson = Lesson::query()->lockForUpdate()->findOrFail($enrollment->lesson_id);

            // 1. Vérification de propriété
            $this->ensureUserOwnsEnrollment($enrollment, $user);

            // 2. Vérification que l'absence n'a pas déjà été annulée
            if ($lockedAbsence->isCancelled()) {
                throw ValidationException::withMessages([
                    'absence' => 'Cette déclaration d\'absence a déjà été annulée.',
                ]);
            }

            // 3. Vérification temporelle
            $this->ensureLessonIsUpcoming($lesson);

            // 4. GARDE-FOU 1 : Vérifier si la place a été prise nommément par un rattrapage
            $isDirectReplacementTaken = Enrollment::query()
                ->where('replaces_absence_id', $lockedAbsence->id)
                ->where('status', 'registered')
                ->lockForUpdate()
                ->exists();

            if ($isDirectReplacementTaken) {
                throw ValidationException::withMessages([
                    'absence' => 'Impossible d\'annuler votre absence : votre place a déjà été réservée par un autre participant en rattrapage.',
                ]);
            }

            // 5. GARDE-FOU 2 : Vérification des capacités physiques réelles de la salle
            $activeEnrollments = Enrollment::query()
                ->where('lesson_id', $lesson->id)
                ->where('status', 'registered')
                ->lockForUpdate()
                ->get();

            // Plafond global (10 max)
            if ($activeEnrollments->count() >= 10) {
                throw ValidationException::withMessages([
                    'absence' => 'Impossible d\'annuler votre absence : la séance a atteint sa capacité maximale (10/10 participants).',
                ]);
            }

            // Plafond par type de poste physique
            if ($enrollment->spot_type === 'wheel') {
                $wheelCount = $activeEnrollments->where('spot_type', 'wheel')->count();
                if ($wheelCount >= 8) {
                    throw ValidationException::withMessages([
                        'absence' => 'Impossible d\'annuler votre absence : tous les tours de cette séance sont actuellement occupés (8/8).',
                    ]);
                }
            } elseif ($enrollment->spot_type === 'handbuilding') {
                $handbuildingCount = $activeEnrollments->where('spot_type', 'handbuilding')->count();
                if ($handbuildingCount >= 4) {
                    throw ValidationException::withMessages([
                        'absence' => 'Impossible d\'annuler votre absence : tous les postes de modelage sont actuellement occupés (4/4).',
                    ]);
                }
            }

            // 6. Rétablissement de la place
            $lockedAbsence->cancelAbsenceNotice();
        });
    }

    /**
     * Vérifie que l'utilisateur est bien le propriétaire de l'inscription.
     *
     * @throws ValidationException
     */
    protected function ensureUserOwnsEnrollment(Enrollment $enrollment, User $user): void
    {
        $participant = $enrollment->module->participant;

        if (! $participant) {
            throw ValidationException::withMessages([
                'enrollment' => 'Participant introuvable pour cette inscription.',
            ]);
        }

        $isOwner = false;

        if ($participant instanceof User && $participant->id === $user->id) {
            $isOwner = true;
        } elseif ($participant instanceof Attendee && $participant->user_id === $user->id) {
            $isOwner = true;
        }

        if (! $isOwner) {
            throw ValidationException::withMessages([
                'unauthorized' => 'Vous n\'êtes pas autorisé à modifier cette inscription.',
            ]);
        }
    }

    /**
     * Vérifie que la séance n'a pas encore débuté.
     *
     * @throws ValidationException
     */
    protected function ensureLessonIsUpcoming(Lesson $lesson): void
    {
        $lessonDate = is_string($lesson->date) ? Carbon::parse($lesson->date) : $lesson->date->copy();
        $startTime = $lesson->effective_start_time ?? '00:00:00';
        $lessonStart = Carbon::parse($lessonDate->format('Y-m-d').' '.$startTime);

        if ($lessonStart->isPast()) {
            throw ValidationException::withMessages([
                'lesson' => 'Cette action est impossible : la séance est déjà passée ou a déjà commencé.',
            ]);
        }
    }
}
