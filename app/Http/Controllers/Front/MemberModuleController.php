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

class MemberModuleController extends Controller
{
    /**
     * Affiche la vue détaillée d'un module avec la timeline de ses séances.
     */
    public function show(Request $request, Module $module): Response
    {
        /** @var User $user */
        $user = $request->user();

        // 1. Contrôle d'accès
        $participant = $module->participant;
        $isAuthorized = false;

        if ($participant instanceof User && $participant->id === $user->id) {
            $isAuthorized = true;
        } elseif ($participant instanceof Attendee && $participant->user_id === $user->id) {
            $isAuthorized = true;
        }

        if (! $isAuthorized) {
            abort(403, 'Vous n\'avez pas accès à ce module.');
        }

        // 2. Chargement des relations avec comptage des places occupées
        $module->load([
            'type',
            'participant',
            'enrollments' => fn($q) => $q->with([
                'lesson' => fn($sub) => $sub->withCount([
                    'enrollments as registered_wheel_count' => fn($cnt) => $cnt->where('status', 'registered')->where('spot_type', 'wheel'),
                    'enrollments as registered_handbuilding_count' => fn($cnt) => $cnt->where('status', 'registered')->where('spot_type', 'handbuilding'),
                ])->with('course.type'),
                'absences' => fn($sub) => $sub->whereNull('cancellation_date')->latest(),
                'replacesAbsence.enrollment.lesson.course',
            ]),
        ]);

        // 3. Quotas de rattrapage
        $makeupsUsedCount = $module->enrollments
            ->where('enrollment_type', 'makeup')
            ->whereIn('status', ['registered', 'absent'])
            ->count();

        $remainingMakeups = max(0, $module->max_makeups_allowed - $makeupsUsedCount);
        $availableMakeupCreditsCount = Absence::query()
            ->availableForMakeup()
            ->whereHas(
                'enrollment',
                fn($query) => $query->where('module_id', $module->id)
            )
            ->count();

        if (! $module->is_active || $remainingMakeups <= 0) {
            $availableMakeupCreditsCount = 0;
        }

        // 4. Tri chronologique de la séquence
        $sortedEnrollments = $module->enrollments
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
            ->values();

        $completedLessonsCount = $sortedEnrollments
            ->filter(function ($enrollment) {
                if (! $enrollment->lesson) {
                    return false;
                }

                // Les rattrapages ne font pas avancer la progression
                // du module de 10 / 20 / 30 séances.
                if ($enrollment->enrollment_type !== 'regular') {
                    return false;
                }

                $lessonDate = $enrollment->lesson->date instanceof \DateTimeInterface
                    ? $enrollment->lesson->date->format('Y-m-d')
                    : (string) $enrollment->lesson->date;

                return $lessonDate < now()->toDateString();
            })
            ->count();

        // 5. Formatage de chaque séance
        $formattedEnrollments = $sortedEnrollments->map(function (Enrollment $enrollment, int $index) {
            /** @var Absence|null $activeAbsence */
            $activeAbsence = $enrollment->absences->first();

            $lessonDate = $enrollment->lesson->date instanceof \DateTimeInterface
                ? $enrollment->lesson->date->format('Y-m-d')
                : (string) $enrollment->lesson->date;

            $isPast = $lessonDate < now()->toDateString();

            $wheelCount = $enrollment->lesson->registered_wheel_count ?? 0;
            $handbuildingCount = $enrollment->lesson->registered_handbuilding_count ?? 0;

            return [
                'id' => $enrollment->id,
                'sequence_number' => $index + 1,
                'status' => $enrollment->status,
                'enrollment_type' => $enrollment->enrollment_type,
                'spot_type' => $enrollment->spot_type,
                'is_past' => $isPast,
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
                'replaces' => $enrollment->replacesAbsence ? [
                    'course_name' => $enrollment->replacesAbsence->enrollment->lesson->course->name,
                    'date' => $enrollment->replacesAbsence->enrollment->lesson->date instanceof \DateTimeInterface
                        ? $enrollment->replacesAbsence->enrollment->lesson->date->format('Y-m-d')
                        : (string) $enrollment->replacesAbsence->enrollment->lesson->date,
                ] : null,
            ];
        });

        return Inertia::render('Front/ModuleDetail', [
            'module' => [
                'id' => $module->id,
                'is_active' => $module->is_active,
                'total_lessons' => $module->total_lessons,
                'completed_lessons' => $completedLessonsCount,
                'max_makeups_allowed' => $module->max_makeups_allowed,
                'makeups_used_count' => $makeupsUsedCount,
                'remaining_makeups' => $remainingMakeups,

                'available_makeup_credits_count' => $availableMakeupCreditsCount,

                'can_book_makeup' =>
                $module->is_active
                    && $remainingMakeups > 0
                    && $availableMakeupCreditsCount > 0,
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
                'is_owner' => $module->participant_type === User::class,
            ],
            'enrollments' => $formattedEnrollments,
        ]);
    }
}
