<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Attendee;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberDashboardController extends Controller
{
    /**
     * Affiche l'espace membre avec les modules, séances et invités.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        // 1. Liste des invités rattachés au compte
        $attendees = $user->attendees()->orderBy('first_name')->get();
        $attendeeIds = $attendees->pluck('id')->toArray();

        // 2. Tous les modules (actifs et passés)
        $modules = Module::query()
            ->where(function ($query) use ($user, $attendeeIds) {
                $query->where(fn($q) => $q->where('participant_type', User::class)->where('participant_id', $user->id))
                    ->orWhere(fn($q) => $q->where('participant_type', Attendee::class)->whereIn('participant_id', $attendeeIds));
            })
            ->with([
                'type',
                'participant',
                'enrollments' => fn($q) => $q->with(['lesson.course', 'absences']),
            ])
            ->orderByDesc('purchase_date')
            ->get();

        $moduleIds = $modules->pluck('id');

        // Formatage optimisé des modules
        $formattedModules = $modules->map(function (Module $module) {
            $makeupsUsedCount = $module->enrollments
                ->where('enrollment_type', 'makeup')
                ->whereIn('status', ['registered', 'absent'])
                ->count();

            $remainingMakeups = max(0, $module->max_makeups_allowed - $makeupsUsedCount);

            $completedLessonsCount = $module->enrollments
                ->filter(function ($enrollment) {
                    if (! $enrollment->lesson) {
                        return false;
                    }

                    if ($enrollment->enrollment_type !== 'regular') {
                        return false;
                    }

                    $lessonDate = $enrollment->lesson->date instanceof \DateTimeInterface
                        ? $enrollment->lesson->date->format('Y-m-d')
                        : (string) $enrollment->lesson->date;

                    return $lessonDate < now()->toDateString();
                })
                ->count();

            return [
                'id' => $module->id,
                'is_active' => $module->is_active,
                'total_lessons' => $module->total_lessons,
                'completed_lessons' => $completedLessonsCount,
                'max_makeups_allowed' => $module->max_makeups_allowed,
                'makeups_used_count' => $makeupsUsedCount,
                'remaining_makeups' => $remainingMakeups,
                'can_book_makeup' => $module->is_active && $remainingMakeups > 0,
                'purchase_date' => $module->purchase_date?->toISOString(),
                'expiration_date' => $module->expiration_date?->toISOString(),
                'type' => [
                    'id' => $module->type->id,
                    'name' => $module->type->name,
                ],
                'participant' => [
                    'id' => $module->participant_id,
                    'type' => $module->participant_type === User::class ? 'user' : 'attendee',
                    'name' => trim(
                        "{$module->participant?->first_name} {$module->participant?->last_name}"
                    ),
                ],
            ];
        });

        // 3. Prochaines séances à venir avec calcul léger des disponibilités de postes
        $upcomingEnrollments = $moduleIds->isEmpty()
            ? collect()
            : Enrollment::query()
            ->whereIn('module_id', $moduleIds)
            ->whereHas('lesson', fn($q) => $q->where('date', '>=', now()->toDateString()))
            ->with([
                'lesson' => fn($q) => $q->withCount([
                    'enrollments as registered_wheel_count' => fn($sub) => $sub->where('status', 'registered')->where('spot_type', 'wheel'),
                    'enrollments as registered_handbuilding_count' => fn($sub) => $sub->where('status', 'registered')->where('spot_type', 'handbuilding'),
                ])->with('course.type'),
                'module.participant',
                'absences' => fn($q) => $q->whereNull('cancellation_date')->latest(),
            ])
            ->get()
            ->sortBy([
                fn($a, $b) => strcmp(
                    $a->lesson->date instanceof \DateTimeInterface ? $a->lesson->date->format('Y-m-d') : (string) $a->lesson->date,
                    $b->lesson->date instanceof \DateTimeInterface ? $b->lesson->date->format('Y-m-d') : (string) $b->lesson->date
                ),
                fn($a, $b) => strcmp(
                    (string) ($a->lesson->effective_start_time ?? ''),
                    (string) ($b->lesson->effective_start_time ?? '')
                ),
            ])
            ->values()
            ->map(function (Enrollment $enrollment) {
                /** @var Absence|null $activeAbsence */
                $activeAbsence = $enrollment->absences->first();

                $lessonDate = $enrollment->lesson->date instanceof \DateTimeInterface
                    ? $enrollment->lesson->date->format('Y-m-d')
                    : (string) $enrollment->lesson->date;

                $wheelCount = $enrollment->lesson->registered_wheel_count ?? 0;
                $handbuildingCount = $enrollment->lesson->registered_handbuilding_count ?? 0;

                return [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'enrollment_type' => $enrollment->enrollment_type,
                    'spot_type' => $enrollment->spot_type,
                    'participant' => [
                        'id' => $enrollment->module->participant_id,
                        'type' => $enrollment->module->participant_type === User::class ? 'user' : 'attendee',
                        'name' => trim(
                            "{$enrollment->module->participant?->first_name} {$enrollment->module->participant?->last_name}"
                        ),
                    ],
                    'lesson' => [
                        'id' => $enrollment->lesson->id,
                        'date' => $lessonDate,
                        'start_time' => $enrollment->lesson->effective_start_time,
                        'end_time' => $enrollment->lesson->effective_end_time,
                        'course_name' => $enrollment->lesson->course->name,
                        'type_name' => $enrollment->lesson->course->type->name,
                        'spots_available' => [
                            'wheel' => max(0, 8 - $wheelCount),
                            'handbuilding' => max(0, 4 - $handbuildingCount),
                        ],
                    ],
                    'absence' => $activeAbsence ? [
                        'id' => $activeAbsence->id,
                        'active' => $activeAbsence->active,
                        'notification_date' => $activeAbsence->notification_date?->toISOString(),
                    ] : null,
                ];
            });

        // 4. Crédits de rattrapage disponibles
        $availableMakeups = $moduleIds->isEmpty()
            ? collect()
            : Absence::query()
            ->availableForMakeup()
            ->whereHas('enrollment.module', function ($query) use ($moduleIds) {
                $query
                    ->where('is_active', true)
                    ->whereIn('id', $moduleIds);
            })
            ->with([
                'enrollment.lesson.course',
                'enrollment.module.participant',
            ])
            ->get()

            // Même règle que dans le calendrier :
            // le module doit encore autoriser un rattrapage.
            ->filter(
                fn(Absence $absence) =>
                $absence->enrollment?->module?->canBookMakeup()
            )
            ->values()
            ->map(function (Absence $absence) {
                $lessonDate = $absence->enrollment->lesson->date instanceof \DateTimeInterface
                    ? $absence->enrollment->lesson->date->format('Y-m-d')
                    : (string) $absence->enrollment->lesson->date;

                return [
                    'id' => $absence->id,
                    'module_id' => $absence->enrollment->module_id,
                    'notification_date' => $absence->notification_date?->toISOString(),

                    'missed_lesson' => [
                        'date' => $lessonDate,
                        'course_name' => $absence->enrollment->lesson->course->name,
                    ],

                    'participant' => [
                        'name' => trim(
                            "{$absence->enrollment->module->participant?->first_name} {$absence->enrollment->module->participant?->last_name}"
                        ),
                    ],
                ];
            });
        return Inertia::render('Front/Membre', [
            'modules' => $formattedModules,
            'upcomingEnrollments' => $upcomingEnrollments,
            'availableMakeups' => $availableMakeups,
            'attendees' => $attendees,
        ]);
    }
}
