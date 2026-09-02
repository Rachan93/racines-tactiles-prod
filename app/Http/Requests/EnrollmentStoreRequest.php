<?php

namespace App\Http\Requests;

use App\Models\Absence;
use App\Models\Attendee;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'enrollment_type' => ['required', 'string', Rule::in(['regular', 'makeup'])],

            // --- MODULE RÉGULIER (MULTI-PARTICIPANTS) ---
            'total_lessons' => ['required_if:enrollment_type,regular', 'nullable', 'integer', 'min:1'],
            'participants' => [
                'exclude_unless:enrollment_type,regular',
                'required',
                'array',
                'min:1',
            ],

            'participants.*.participant_id' => [
                'required',
                'integer',
            ],

            'participants.*.participant_type' => [
                'required',
                'string',
                Rule::in([
                    User::class,
                    'App\Models\Attendee',
                ]),
            ],

            'participants.*.spot_type' => [
                'required',
                'string',
                Rule::in([
                    'handbuilding',
                    'wheel',
                ]),
            ],

            // --- RATTRAPAGE UNITAIRE ---
            'spot_type' => ['required_if:enrollment_type,makeup', 'nullable', 'string', Rule::in(['handbuilding', 'wheel'])],
            'absence_id' => ['required_if:enrollment_type,makeup', 'nullable', 'integer', 'exists:absences,id'],
            'module_id' => ['required_if:enrollment_type,makeup', 'nullable', 'integer', 'exists:modules,id'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                /** @var Lesson|null $lesson */
                $lesson = Lesson::with(['course.type'])->find($this->input('lesson_id'));
                if (! $lesson) {
                    return;
                }

                $user = $this->user();
                $today = Carbon::today();
                $lessonDate = Carbon::parse($lesson->date)->startOfDay();

                // 1. SÉCURITÉ : Cours actif, non annulé et futur
                if (! $lesson->course->is_active) {
                    $validator->errors()->add('lesson_id', 'Ce cours n\'est plus ouvert aux réservations.');
                    return;
                }

                if ($lesson->is_cancelled) {
                    $validator->errors()->add('lesson_id', 'Cette séance a été annulée.');
                    return;
                }

                if ($lessonDate->lt($today)) {
                    $validator->errors()->add('lesson_id', 'Impossible de réserver une séance dans le passé.');
                    return;
                }

                $typeName = strtolower($lesson->course->type->name ?? '');
                $isCollective = str_contains($typeName, 'collectif') || (int) $lesson->course->type_id === 1;
                $isWithinJ6 = $lessonDate->gte($today) && $lessonDate->lte($today->copy()->addDays(6));

                $enrollmentType = $this->input('enrollment_type');
                $userAttendeeIds = Attendee::where('user_id', $user->id)->pluck('id')->toArray();

                // ==========================================
                // CAS 1 : MODULE RÉGULIER
                // ==========================================
                if ($enrollmentType === 'regular') {
                    $participants = $this->input('participants', []);
                    $totalLessons = (int) $this->input('total_lessons', 10);

                    if ($isCollective) {
                        if ($totalLessons % 10 !== 0 || $totalLessons < 10) {
                            $validator->errors()->add('total_lessons', 'Pour les cours collectifs, la durée doit être un multiple de 10 séances (10, 20, 30...).');
                            return;
                        }

                        if (! $isWithinJ6) {
                            $validator->errors()->add('lesson_id', 'Les inscriptions à un nouveau module de cours collectifs ne sont ouvertes que 6 jours à l\'avance.');
                            return;
                        }
                    } else {
                        $totalLessons = Lesson::where('course_id', $lesson->course_id)
                            ->where('date', '>=', $lesson->date)
                            ->where('is_cancelled', false)
                            ->count();
                    }

                    $sequenceLessons = Lesson::where('course_id', $lesson->course_id)
                        ->where('date', '>=', $lesson->date)
                        ->where('is_cancelled', false)
                        ->orderBy('date', 'asc')
                        ->take($totalLessons)
                        ->get();

                    if ($sequenceLessons->count() < $totalLessons) {
                        $validator->errors()->add(
                            'total_lessons',
                            "Seules {$sequenceLessons->count()} séance(s) sont programmées pour ce cours. Impossible de réserver {$totalLessons} séances."
                        );
                        return;
                    }

                    $sequenceLessonIds = $sequenceLessons->pluck('id')->toArray();
                    $requestedHandbuilding = 0;
                    $requestedWheel = 0;
                    $seenParticipants = [];

                    foreach ($participants as $index => $p) {
                        $pType = $p['participant_type'];
                        $pId = (int) $p['participant_id'];
                        $pSpot = $p['spot_type'];

                        if ($pSpot === 'handbuilding') $requestedHandbuilding++;
                        if ($pSpot === 'wheel') $requestedWheel++;

                        if ($pType === 'App\Models\Attendee' && ! in_array($pId, $userAttendeeIds)) {
                            $validator->errors()->add("participants.{$index}.participant_id", "Cet invité n'est pas rattaché à votre compte.");
                            continue;
                        }

                        if ($pType === User::class && $pId !== $user->id) {
                            $validator->errors()->add("participants.{$index}.participant_id", "Identifiant utilisateur invalide.");
                            continue;
                        }

                        $key = "{$pType}:{$pId}";
                        if (in_array($key, $seenParticipants)) {
                            $validator->errors()->add("participants.{$index}.participant_id", "Ce participant a été ajouté plusieurs fois.");
                            continue;
                        }
                        $seenParticipants[] = $key;

                       
                        $conflictingEnrollment = Enrollment::query()
                            ->whereIn('lesson_id', $sequenceLessonIds)
                            ->whereIn('status', ['registered', 'absent'])
                            ->whereHas('module', function ($q) use ($pType, $pId) {
                                $q->where('participant_type', $pType)
                                    ->where('participant_id', $pId);
                            })
                            ->first();

                        if ($conflictingEnrollment) {
                            $message = $conflictingEnrollment->status === 'absent'
                                ? "Ce participant est déjà noté absent sur l'une des séances de cette série. Annulez d'abord l'avis d'absence depuis votre espace membre si vous souhaitez reprendre cette place."
                                : "Ce participant est déjà inscrit à l'une des séances de cette série.";

                            $validator->errors()->add(
                                "participants.{$index}.participant_id",
                                $message
                            );
                        }
                    }

                    // Capacités pour nouveaux modules
                    $regularSeatsHandbuilding = $lesson->enrollments()
                        ->where('spot_type', 'handbuilding')
                        ->where('enrollment_type', 'regular')
                        ->whereIn('status', ['registered', 'absent'])
                        ->count();

                    $regularSeatsWheel = $lesson->enrollments()
                        ->where('spot_type', 'wheel')
                        ->where('enrollment_type', 'regular')
                        ->whereIn('status', ['registered', 'absent'])
                        ->count();

                    $roomAvailable = max(0, 10 - ($regularSeatsHandbuilding + $regularSeatsWheel));
                    $availableHandbuilding = max(0, min(4 - $regularSeatsHandbuilding, $roomAvailable));
                    $availableWheel = max(0, min(8 - $regularSeatsWheel, $roomAvailable));

                    if (($requestedHandbuilding + $requestedWheel) > $roomAvailable) {
                        $validator->errors()->add('participants', "Il ne reste que {$roomAvailable} place(s) disponible(s) pour un nouveau module.");
                    } elseif ($requestedHandbuilding > $availableHandbuilding) {
                        $validator->errors()->add('participants', "Il ne reste que {$availableHandbuilding} place(s) disponible(s) en Modelage.");
                    } elseif ($requestedWheel > $availableWheel) {
                        $validator->errors()->add('participants', "Il ne reste que {$availableWheel} place(s) disponible(s) au Tour.");
                    }
                }

                // ==========================================
                // CAS 2 : RATTRAPAGE UNITAIRE (SÉLECTION D'ABSENCE)
                // ==========================================
                if ($enrollmentType === 'makeup') {
                    if (! $isCollective) {
                        $validator->errors()->add('lesson_id', 'Les rattrapages sont réservés aux cours collectifs.');
                        return;
                    }

                    $absenceId = (int) $this->input('absence_id');
                    $moduleId = (int) $this->input('module_id');
                    $spotType = $this->input('spot_type');

                    /** @var Absence|null $absence */
                    $absence = Absence::with(['enrollment.module.participant'])->find($absenceId);
                    /** @var Module|null $module */
                    $module = Module::find($moduleId);

                    if (! $absence || ! $absence->isAvailableForMakeup()) {
                        $validator->errors()->add('absence_id', 'Le crédit d\'absence sélectionné n\'est plus disponible ou a déjà été utilisé.');
                        return;
                    }

                    if (! $module || ! $module->canBookMakeup()) {
                        $validator->errors()->add('absence_id', 'Le quota maximal de rattrapages pour ce module est atteint.');
                        return;
                    }

                    // Sécurité : L'absence doit bien appartenir à l'utilisateur connecté ou à un de ses invités
                    $pType = $absence->enrollment->module->participant_type;
                    $pId = $absence->enrollment->module->participant_id;

                    if ($pType === 'App\Models\Attendee' && ! in_array($pId, $userAttendeeIds)) {
                        $validator->errors()->add('absence_id', 'Ce crédit d\'absence n\'appartient pas à votre compte.');
                        return;
                    }

                    if ($pType === User::class && $pId !== $user->id) {
                        $validator->errors()->add('absence_id', 'Ce crédit d\'absence n\'appartient pas à votre compte.');
                        return;
                    }

                    // 1. Anti-doublon : Le participant est-il déjà inscrit à ce cours ?
                    $isAlreadyRegistered = Enrollment::where('lesson_id', $lesson->id)
                        ->where('status', 'registered')
                        ->whereHas('module', fn($q) => $q->where('participant_type', $pType)->where('participant_id', $pId))
                        ->exists();

                    if ($isAlreadyRegistered) {
                        $validator->errors()->add('absence_id', 'Ce participant est déjà inscrit à cette séance.');
                        return;
                    }

                    // 2. Anti-doublon SQL : Le module a-t-il déjà un enrollment sur cette séance ?
                    $isModuleAlreadyInLesson = Enrollment::where('lesson_id', $lesson->id)
                        ->where('module_id', $module->id)
                        ->exists();

                    if ($isModuleAlreadyInLesson) {
                        $validator->errors()->add('absence_id', 'Une inscription pour ce module existe déjà sur cette séance.');
                        return;
                    }

                    // 3. Anti-rattrapage sur sa propre absence
                    $isOriginallyAbsentHere = Enrollment::where('lesson_id', $lesson->id)
                        ->where('status', 'absent')
                        ->whereHas('module', fn($q) => $q->where('participant_type', $pType)->where('participant_id', $pId))
                        ->exists();

                    if ($isOriginallyAbsentHere) {
                        $validator->errors()->add(
                            'absence_id',
                            'Ce participant est déjà noté absent sur cette séance. Rendez-vous dans votre espace membre pour annuler votre absence et reprendre votre place.'
                        );
                        return;
                    }

                    // 4. Places de rattrapage disponibles sur la séance
                    $absentCount = $lesson->enrollments()
                        ->where('spot_type', $spotType)
                        ->where('enrollment_type', 'regular')
                        ->where('status', 'absent')
                        ->count();
                    $makeupCount = $lesson->enrollments()->where('spot_type', $spotType)->where('enrollment_type', 'makeup')->where('status', 'registered')->count();
                    $absenceHoles = max(0, $absentCount - $makeupCount);

                    $regularSeats = $lesson->enrollments()->where('spot_type', $spotType)->where('enrollment_type', 'regular')->whereIn('status', ['registered', 'absent'])->count();
                    $maxSpot = $spotType === 'wheel' ? 8 : 4;
                    $standardFree = max(0, $maxSpot - $regularSeats);

                    $availableMakeupSpots = $isWithinJ6 ? ($absenceHoles + $standardFree) : $absenceHoles;

                    if ($availableMakeupSpots <= 0) {
                        $validator->errors()->add('spot_type', 'Aucune place de rattrapage n\'est disponible sur ce poste pour cette séance.');
                    }
                }
            }
        ];
    }
}
