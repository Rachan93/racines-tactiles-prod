<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemberEnrollmentController extends Controller
{
    /**
     * Modifie le type de poste (Tour <-> Modelage) pour une séance future.
     *
     * @throws ValidationException
     */
    public function updateSpotType(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'spot_type' => ['required', 'string', Rule::in(['wheel', 'handbuilding'])],
        ]);

        $targetSpotType = $validated['spot_type'];
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($enrollment, $targetSpotType, $user) {
            /** @var Enrollment $lockedEnrollment */
            $lockedEnrollment = Enrollment::query()
                ->with(['module.participant', 'lesson'])
                ->lockForUpdate()
                ->findOrFail($enrollment->id);

            // 1. Contrôle de propriété
            $participant = $lockedEnrollment->module->participant;
            $isOwner = ($participant instanceof User && $participant->id === $user->id)
                || ($participant instanceof Attendee && $participant->user_id === $user->id);

            if (! $isOwner) {
                abort(403, 'Action non autorisée sur cette inscription.');
            }

            // 2. Vérification du statut
            if (! $lockedEnrollment->isRegistered()) {
                throw ValidationException::withMessages([
                    'spot_type' => 'Impossible de changer de poste pour une séance où vous êtes marqué absent.',
                ]);
            }

            // 3. Vérification temporelle (la séance ne doit pas être passée ou en cours)
            $lesson = $lockedEnrollment->lesson;
            $lessonDate = $lesson->date instanceof \DateTimeInterface
                ? $lesson->date->format('Y-m-d')
                : (string) $lesson->date;
            $startTime = $lesson->effective_start_time ?? '00:00:00';
            $lessonStart = Carbon::parse($lessonDate.' '.$startTime);

            if ($lessonStart->isPast()) {
                throw ValidationException::withMessages([
                    'spot_type' => 'Impossible de changer de poste : la séance est déjà passée ou a commencé.',
                ]);
            }

            // Si le poste est déjà celui demandé, rien à faire
            if ($lockedEnrollment->spot_type === $targetSpotType) {
                return;
            }

            // 4. Verrouillage de la leçon et contrôle des capacités physiques de la salle
            /** @var Lesson $lockedLesson */
            $lockedLesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->id);

            $activeEnrollments = Enrollment::query()
                ->where('lesson_id', $lockedLesson->id)
                ->where('status', 'registered')
                ->where('id', '!=', $lockedEnrollment->id) // On exclut l'inscription en cours
                ->lockForUpdate()
                ->get();

            if ($targetSpotType === 'wheel') {
                $wheelCount = $activeEnrollments->where('spot_type', 'wheel')->count();
                if ($wheelCount >= 8) {
                    throw ValidationException::withMessages([
                        'spot_type' => 'Impossible de basculer sur un tour : tous les tours (8/8) sont actuellement occupés pour cette séance.',
                    ]);
                }
            } elseif ($targetSpotType === 'handbuilding') {
                $handbuildingCount = $activeEnrollments->where('spot_type', 'handbuilding')->count();
                if ($handbuildingCount >= 4) {
                    throw ValidationException::withMessages([
                        'spot_type' => 'Impossible de basculer sur le modelage : tous les postes de modelage (4/4) sont actuellement occupés pour cette séance.',
                    ]);
                }
            }

            // 5. Enregistrement de la modification
            $lockedEnrollment->update(['spot_type' => $targetSpotType]);
        });

        $label = $targetSpotType === 'wheel' ? 'Tournage 🏺' : 'Modelage 🖐️';

        return redirect()->back()->with('success', "Votre poste a été modifié pour cette séance : {$label}.");
    }
}
