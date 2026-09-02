<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseStoreRequest;
use App\Http\Requests\CourseUpdateRequest;
use App\Models\Attendee;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Lesson;
use App\Models\Type;
use App\Models\User;
use App\Services\LessonGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Catalogue des cours ultra-performant avec supervision.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $rawTargets = $request->query('search_targets', ['name', 'instructor']);
        $searchTargets = is_array($rawTargets) ? array_values($rawTargets) : ['name', 'instructor'];

        $typeId = $request->query('type_id', '');
        $year = $request->query('year', '');
        $status = $request->query('status', 'all');
        $sort = $request->query('sort', 'oldest'); // 'oldest' par défaut

        $today = now()->toDateString();

        $coursesQuery = Course::query()
            ->with([
                'type:id,name',
                'instructor:id,first_name,last_name',
                'lessons' => function ($q) {
                    $q->orderBy('date', 'asc')
                        ->with(['overrideInstructor:id,first_name,last_name'])
                        ->withCount([
                            'enrollments as registered_wheel_count' => fn ($sub) => $sub->where('status', 'registered')->where('spot_type', 'wheel'),
                            'enrollments as registered_handbuilding_count' => fn ($sub) => $sub->where('status', 'registered')->where('spot_type', 'handbuilding'),
                            'enrollments as total_registered_count' => fn ($sub) => $sub->where('status', 'registered'),
                        ]);
                },
            ])
            // 1. Recherche ciblée selon les cases cochées
            ->when($search, function ($q) use ($search, $searchTargets) {
                $q->where(function ($sub) use ($search, $searchTargets) {
                    if (in_array('name', $searchTargets)) {
                        $sub->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('sub_type', 'like', "%{$search}%");
                    }

                    if (in_array('instructor', $searchTargets)) {
                        $sub->orWhereHas('instructor', function ($inst) use ($search) {
                            $inst->where('first_name', 'like', "%{$search}%")
                                 ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }

                    if (in_array('student', $searchTargets)) {
                        $sub->orWhereHas('lessons.enrollments.module', function ($mod) use ($search) {
                            $mod->whereHasMorph('participant', [User::class, Attendee::class], function ($pQuery, $type) use ($search) {
                                $pQuery->where(function ($nq) use ($search) {
                                    $nq->where('first_name', 'like', "%{$search}%")
                                       ->orWhere('last_name', 'like', "%{$search}%");
                                });
                                if ($type === User::class) {
                                    $pQuery->orWhere('email', 'like', "%{$search}%");
                                }
                            });
                        });
                    }
                });
            })
            // 2. Filtre par type
            ->when($typeId, function ($q, $typeId) {
                $q->where('type_id', $typeId);
            })
            // 3. Filtre par année
            ->when($year && $year !== 'all', function ($q) use ($year) {
                $q->whereYear('first_lesson_date', $year);
            })
            // 4. Filtre par statut
            ->when($status && $status !== 'all', function ($q) use ($status) {
                match ($status) {
                    'upcoming' => $q->upcomingOrOngoing(),
                    'past' => $q->past(),
                    'inactive' => $q->where('is_active', false),
                    'active' => $q->active(),
                    default => null,
                };
            });

        // 5. Options de tri (Plus ancien par défaut)
        if ($sort === 'newest') {
            $coursesQuery->orderBy('first_lesson_date', 'desc');
        } elseif ($sort === 'nearest_lesson') {
            $coursesQuery->addSelect([
                'nearest_lesson_date' => Lesson::select('date')
                    ->whereColumn('course_id', 'courses.id')
                    ->where('date', '>=', $today)
                    ->where('is_cancelled', false)
                    ->orderBy('date', 'asc')
                    ->limit(1),
            ])->orderByRaw('CASE WHEN nearest_lesson_date IS NULL THEN 1 ELSE 0 END, nearest_lesson_date ASC');
        } else {
            $coursesQuery->orderBy('first_lesson_date', 'asc');
        }

        $courses = $coursesQuery->get()->map(function (Course $course) use ($today) {
            $lessons = $course->lessons;
            $totalLessons = $lessons->count();
            $cancelledCount = $lessons->where('is_cancelled', true)->count();
            $overriddenCount = $lessons->where('is_overridden', true)->count();

            // Nombre d'élèves inscrits sur des séances futures (pour autoriser ou bloquer la suppression du cours)
            $futureRegisteredCount = $lessons
                ->filter(fn ($l) => $l->date && $l->date->toDateString() >= $today && ! $l->is_cancelled)
                ->sum('total_registered_count');

            $futureOverriddenCount = $lessons->filter(function ($l) use ($today) {
                return $l->is_overridden && ! $l->is_cancelled && $l->date && $l->date->toDateString() >= $today;
            })->count();

            $totalSpotsMax = $course->default_spots_max_wheel + $course->default_spots_max_handbuilding;
            $avgRegistered = $totalLessons > 0
                ? round($lessons->sum('total_registered_count') / $totalLessons, 1)
                : 0;

            $isPast = ($course->end_date && $course->end_date->toDateString() < $today)
                && ! $lessons->contains(fn ($l) => $l->date && $l->date->toDateString() >= $today);

            return [
                'id' => $course->id,
                'name' => $course->name,
                'name_en' => $course->name_en,
                'sub_type' => $course->sub_type,
                'subtitle' => $course->subtitle,
                'subtitle_en' => $course->subtitle_en,
                'description' => $course->description,
                'description_en' => $course->description_en,
                'practical_info' => $course->practical_info,
                'practical_info_en' => $course->practical_info_en,
                'is_active' => (bool) $course->is_active,
                'is_featured' => (bool) $course->is_featured,
                'is_past' => $isPast,
                'future_registered_count' => $futureRegisteredCount,
                'type' => [
                    'id' => $course->type->id ?? null,
                    'name' => $course->type->name ?? 'Général',
                ],
                'instructor' => [
                    'id' => $course->instructor->id ?? null,
                    'name' => $course->instructor ? ($course->instructor->first_name . ' ' . $course->instructor->last_name) : 'Non assigné',
                ],
                'default_instructor_id' => $course->default_instructor_id,
                'first_lesson_date' => $course->first_lesson_date ? $course->first_lesson_date->toDateString() : null,
                'first_lesson_date_formatted' => $course->first_lesson_date ? $course->first_lesson_date->translatedFormat('d M Y') : '-',
                'end_date' => $course->end_date ? $course->end_date->toDateString() : null,
                'end_date_formatted' => $course->end_date ? $course->end_date->translatedFormat('d M Y') : '-',
                'default_start_time' => substr($course->default_start_time, 0, 5),
                'default_end_time' => substr($course->default_end_time, 0, 5),
                'frequency' => $course->frequency,
                'default_spots_max_wheel' => $course->default_spots_max_wheel,
                'default_spots_max_handbuilding' => $course->default_spots_max_handbuilding,
                'default_spots_total' => $totalSpotsMax,
                'default_price' => (float) $course->default_price,
                // Statistiques du cours
                'stats' => [
                    'total_lessons' => $totalLessons,
                    'cancelled_lessons' => $cancelledCount,
                    'overridden_lessons' => $overriddenCount,
                    'future_overridden_lessons' => $futureOverriddenCount,
                    'avg_registered' => $avgRegistered,
                ],
                // Liste légère des séances (chargement des élèves on-demand)
                'lessons' => $lessons->map(function (Lesson $lesson) use ($course, $today) {
                    $isLessonPast = $lesson->date ? $lesson->date->toDateString() < $today : false;

                    // Diffs indépendants
                    $isInstructorOverridden = $lesson->is_overridden && $lesson->override_instructor_id && $lesson->override_instructor_id !== $course->default_instructor_id;
                    $isTimeOverridden = $lesson->is_overridden && ($lesson->override_start_time !== null || $lesson->override_end_time !== null);
                    $isWheelOverridden = $lesson->is_overridden && $lesson->override_spots_max_wheel !== null;
                    $isHandbuildingOverridden = $lesson->is_overridden && $lesson->override_spots_max_handbuilding !== null;
                    $isPriceOverridden = $lesson->is_overridden && $lesson->override_price !== null;

                    return [
                        'id' => $lesson->id,
                        'course_id' => $lesson->course_id,
                        'date' => $lesson->date ? $lesson->date->toDateString() : null,
                        'date_formatted' => $lesson->date ? ucfirst($lesson->date->translatedFormat('D d M Y')) : '-',
                        'day_name' => $lesson->date ? ucfirst($lesson->date->translatedFormat('l')) : '-',
                        'start_time' => substr($lesson->effective_start_time, 0, 5),
                        'end_time' => substr($lesson->effective_end_time, 0, 5),
                        'instructor' => [
                            'id' => $lesson->effective_instructor?->id,
                            'name' => $lesson->effective_instructor ? ($lesson->effective_instructor->first_name . ' ' . $lesson->effective_instructor->last_name) : 'Non assigné',
                        ],
                        'spots' => [
                            'wheel_booked' => $lesson->registered_wheel_count ?? 0,
                            'wheel_max' => $lesson->effective_spots_max_wheel,
                            'handbuilding_booked' => $lesson->registered_handbuilding_count ?? 0,
                            'handbuilding_max' => $lesson->effective_spots_max_handbuilding,
                            'total_booked' => $lesson->total_registered_count ?? 0,
                            'total_max' => $lesson->effective_spots_max_wheel + $lesson->effective_spots_max_handbuilding,
                        ],
                        'price' => $lesson->effective_price,
                        'is_overridden' => (bool) $lesson->is_overridden,
                        'is_cancelled' => (bool) $lesson->is_cancelled,
                        'cancellation_reason' => $lesson->cancellation_reason,
                        'is_past' => $isLessonPast,
                        // Diffs indépendants pour la vue
                        'diffs' => [
                            'instructor' => $isInstructorOverridden,
                            'time' => $isTimeOverridden,
                            'wheel' => $isWheelOverridden,
                            'handbuilding' => $isHandbuildingOverridden,
                            'price' => $isPriceOverridden,
                        ],
                    ];
                }),
            ];
        });

        // Années disponibles pour le filtre
        $availableYears = Course::selectRaw('YEAR(first_lesson_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values()
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [2025, 2026, 2027, 2028];
        }

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'types' => Type::all(['id', 'name']),
            'instructors' => Instructor::all(['id', 'first_name', 'last_name']),
            'years' => $availableYears,
            'filters' => [
                'search' => $search,
                'search_targets' => array_values($searchTargets),
                'type_id' => $typeId,
                'year' => $year,
                'status' => $status,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Page de création d'un cours.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Courses/Create', [
            'types' => Type::all(['id', 'name', 'allows_makeup', 'makeup_amount']),
            'instructors' => Instructor::all(['id', 'first_name', 'last_name']),
        ]);
    }

    /**
     * Endpoint de prévisualisation réactive.
     */
    public function preview(Request $request, LessonGeneratorService $generatorService): JsonResponse
    {
        $validated = $request->validate([
            'first_lesson_date' => 'required|date',
            'end_date' => 'required|date',
            'frequency' => 'required|integer|min:1',
            'type_id' => 'nullable|integer|exists:types,id',
            'exclude_public_holidays' => 'nullable|boolean',
            'exclude_school_holidays' => 'nullable|boolean',
            'exclude_studio_closures' => 'nullable|boolean',
            'exclude_weekends' => 'nullable|boolean',
        ]);

        $schedule = $generatorService->previewSchedule($validated);

        return response()->json($schedule);
    }

    /**
     * Enregistre le cours et génère les séances.
     */
    public function store(CourseStoreRequest $request, LessonGeneratorService $generatorService): RedirectResponse
    {
        $validated = $request->validated();
        $confirmedDates = $validated['confirmed_dates'];

        $generatorService->createCourseWithLessons($validated, $confirmedDates);

        return redirect()->route('courses.index');
    }

    /**
     * Met à jour les valeurs par défaut et le contenu du cours parent.
     */
    public function update(CourseUpdateRequest $request, Course $course): RedirectResponse
    {
        $validated = $request->validated();
        $resetLessonIds = array_map('intval', (array) ($validated['reset_lesson_ids'] ?? []));
        $resetFutureOverrides = (bool) ($validated['reset_future_overrides'] ?? false);

        DB::transaction(function () use ($course, $validated, $resetLessonIds, $resetFutureOverrides) {
            $course->update([
                'name' => $validated['name'],
                'name_en' => $validated['name_en'] ?? null,
                'sub_type' => $validated['sub_type'] ?? null,
                'subtitle' => $validated['subtitle'] ?? null,
                'subtitle_en' => $validated['subtitle_en'] ?? null,
                'description' => $validated['description'] ?? null,
                'description_en' => $validated['description_en'] ?? null,
                'practical_info' => $validated['practical_info'] ?? null,
                'practical_info_en' => $validated['practical_info_en'] ?? null,
                'default_instructor_id' => $validated['default_instructor_id'],
                'default_start_time' => $validated['default_start_time'],
                'default_end_time' => $validated['default_end_time'],
                'default_spots_max_wheel' => $validated['default_spots_max_wheel'],
                'default_spots_max_handbuilding' => $validated['default_spots_max_handbuilding'],
                'default_price' => $validated['default_price'],
                'is_active' => filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN),
                'is_featured' => filter_var($validated['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);

            // Réinitialisation en BDD sans écraser à null
            if (! empty($resetLessonIds)) {
                Lesson::where('course_id', $course->id)
                    ->whereIn('id', $resetLessonIds)
                    ->update(['is_overridden' => false]);
            } elseif ($resetFutureOverrides) {
                Lesson::where('course_id', $course->id)
                    ->where('date', '>=', now()->toDateString())
                    ->where('is_cancelled', false)
                    ->where('is_overridden', true)
                    ->update(['is_overridden' => false]);
            }
        });

        return redirect()->back();
    }

    /**
     * Supprime un cours uniquement si AUCUN élève n'est inscrit sur une séance future.
     */
    public function delete(Course $course): RedirectResponse
    {
        $today = now()->toDateString();

        $hasFutureEnrollments = $course->lessons()
            ->where('date', '>=', $today)
            ->where('is_cancelled', false)
            ->whereHas('enrollments', fn ($q) => $q->where('status', 'registered'))
            ->exists();

        if ($hasFutureEnrollments) {
            abort(422, "Impossible de supprimer ce cours : des élèves y sont inscrits sur des séances futures.");
        }

        $course->delete();

        return redirect()->route('courses.index');
    }
}
