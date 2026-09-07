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
        $filters = $this->normalizeFilters($request->only([
            'type_id',
            'course_id',
            'spot_type',
            'hide_full',
            'only_makeups',
            'start_date',
            'end_date',
        ]));

        $user = $request->user();

        $lessons = $this->availabilityService
            ->getLessonsForCalendar($filters, $user);

        $attendees = $user
            ? $user->attendees()->orderBy('first_name')->get()
            : [];

        $activeAbsences = $user
            ? Absence::query()
                ->availableForMakeup()
                ->whereHas('enrollment.module', function ($query) use ($user) {
                    $query
                        ->where('is_active', true)
                        ->where(function ($participants) use ($user) {
                            $participants
                                ->where(function ($participant) use ($user) {
                                    $participant
                                        ->where('participant_type', 'App\Models\User')
                                        ->where('participant_id', $user->id);
                                })
                                ->orWhere(function ($participant) use ($user) {
                                    $participant
                                        ->where('participant_type', 'App\Models\Attendee')
                                        ->whereIn(
                                            'participant_id',
                                            $user->attendees()->select('id')
                                        );
                                });
                        });
                })
                ->with([
                    'enrollment.module.participant',
                    'enrollment.lesson.course',
                ])
                ->orderBy('notification_date', 'asc')
                ->get()
                ->filter(
                    fn ($absence) => $absence->enrollment?->module?->canBookMakeup()
                )
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
        $filters = $this->normalizeFilters($request->only([
            'type_id',
            'course_id',
            'spot_type',
            'hide_full',
            'only_makeups',
            'from_date',
        ]));

        return response()->json([
            'date' => $this->availabilityService->getNextLessonDate($filters),
        ]);
    }

    /**
     * Applique les mêmes règles au calendrier et à la prochaine séance.
     */
    private function normalizeFilters(array $filters): array
    {
        $typeId = filter_var(
            $filters['type_id'] ?? 1,
            FILTER_VALIDATE_INT
        );

        // 1 = collectifs, 2 = stages, 3 = privés.
        $filters['type_id'] = in_array($typeId, [1, 2, 3], true)
            ? $typeId
            : 1;

        // Les rattrapages concernent uniquement les cours collectifs.
        if ($filters['type_id'] !== 1) {
            $filters['only_makeups'] = 0;
        }

        return $filters;
    }
}
