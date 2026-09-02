<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonCalendarResource;
use App\Models\Absence;
use App\Services\LessonAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendrierController extends Controller
{
    public function __construct(
        protected LessonAvailabilityService $availabilityService
    ) {}

    public function __invoke(Request $request): Response
    {
        // 1. Récupération de l'INTÉGRALITÉ des filtres (corrige les switchs)
        $filters = $request->only([
            'type_id',
            'course_id',
            'spot_type',
            'hide_full',
            'only_makeups',
            'start_date',
            'end_date',
        ]);

        $user = $request->user();

        // 2. Leçons calculées avec les filtres et détection des inscriptions du compte
        $lessons = $this->availabilityService->getLessonsForCalendar($filters, $user);

        $attendees = $user ? $user->attendees()->orderBy('first_name')->get() : [];

        // 3. Récupération des crédits d'absence réellement exploitables avec relations complètes
        $activeAbsences = $user
            ? Absence::query()
                ->availableForMakeup()
                ->whereHas('enrollment.module', function ($query) use ($user) {
                    $query->where('is_active', true)
                        ->where(function ($q) use ($user) {
                            $q->where('participant_type', 'App\Models\User')
                                ->where('participant_id', $user->id);
                        })->orWhere(function ($q) use ($user) {
                            $q->where('participant_type', 'App\Models\Attendee')
                                ->whereIn('participant_id', $user->attendees()->select('id'));
                        });
                })
                ->with([
                    'enrollment.module.participant',
                    'enrollment.lesson.course',
                ])
                ->orderBy('notification_date', 'asc')
                ->get()
                // Garde uniquement les absences dont le module a du quota restant
                ->filter(fn ($absence) => $absence->enrollment?->module?->canBookMakeup())
                ->values()
            : [];

        return Inertia::render('Front/Calendrier', [
            'events' => LessonCalendarResource::collection($lessons)->resolve(),
            'filters' => $filters,
            'attendees' => $attendees,
            'activeAbsences' => $activeAbsences,
        ]);
    }

    public function nextLesson(Request $request): JsonResponse
    {
        $filters = $request->only([
            'type_id',
            'course_id',
            'spot_type',
            'hide_full',
            'only_makeups',
            'from_date',
        ]);

        return response()->json([
            'date' => $this->availabilityService->getNextLessonDate($filters),
        ]);
    }
}
