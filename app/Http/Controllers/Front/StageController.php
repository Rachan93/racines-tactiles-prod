<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonCalendarResource;
use App\Models\Absence;
use App\Models\Course;
use App\Services\LessonAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StageController extends Controller
{
    public function __construct(
        protected LessonAvailabilityService $availabilityService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Stages actifs, mis en avant et non terminés.
        $courses = Course::query()
            ->active()
            ->upcomingOrOngoing()
            ->where('is_featured', true)
            ->whereHas('type', function ($query) {
                $query->where('name', 'like', '%stage%');
            })
            ->with(['type', 'defaultInstructor'])
            ->orderBy('first_lesson_date')
            ->get();

        // 2. Disponibilités des séances associées.
        $courseIds = $courses->pluck('id')->toArray();

        $lessons = ! empty($courseIds)
            ? $this->availabilityService->getLessonsForCalendar([
                'start_date' => Carbon::now()
                    ->startOfDay()
                    ->toDateString(),
                'end_date' => Carbon::now()
                    ->addYear()
                    ->endOfDay()
                    ->toDateString(),
            ])->filter(
                fn ($lesson) => in_array($lesson->course_id, $courseIds)
            )
            : collect();

        $formattedLessons = LessonCalendarResource::collection($lessons)
            ->resolve();

        $rawLessonsById = $lessons->keyBy('id');

        $lessonsByCourse = collect($formattedLessons)
            ->groupBy(function ($lesson) use ($rawLessonsById) {
                return $rawLessonsById->get($lesson['id'])?->course_id;
            });

        // 3. Catégories et ordre d'affichage.
        $categoriesOrder = [
            'wheel' => [
                'key' => 'wheel',
                'title' => 'Stages de tournage',
                'description' => 'Perfectionnez votre technique au tour avec des sessions intensives dédiées au geste et à la précision.',
            ],
            'external' => [
                'key' => 'external',
                'title' => 'Intervenants externes',
                'description' => 'Masterclasses et stages animés par des céramistes et artistes invités.',
            ],
            'themed' => [
                'key' => 'themed',
                'title' => 'Journées thématiques',
                'description' => 'Explorez des techniques spécifiques : décors, cuissons spéciales, recherche d’émaux, modelage et expérimentations.',
            ],
            'one-off' => [
                'key' => 'one-off',
                'title' => 'Stages ponctuels',
                'description' => 'Des formats ponctuels pour découvrir ou approfondir une technique lors d’une session dédiée.',
            ],
        ];

        // 4. Données des cartes, regroupées par catégorie.
        $categories = [];

        foreach ($categoriesOrder as $key => $meta) {
            $matchingCourses = $courses
                ->where('sub_type', $key)
                ->map(function ($course) use ($lessonsByCourse) {
                    $courseLessons = $lessonsByCourse
                        ->get($course->id, collect())
                        ->values()
                        ->all();

                    $firstLesson = $courseLessons[0] ?? null;

                    $hasEnglish = ! empty($course->name_en)
                        || ! empty($course->description_en)
                        || ! empty($course->subtitle_en)
                        || ! empty($course->practical_info_en);

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

                        // URL directement utilisable dans le src d'une image.
                        'cover_image' => $this->coverImageUrl(
                            $course->cover_image
                        ),

                        'first_lesson_date' => $course->first_lesson_date
                            ?->format('Y-m-d'),
                        'end_date' => $course->end_date?->format('Y-m-d'),
                        'default_start_time' => $course->default_start_time,
                        'default_end_time' => $course->default_end_time,
                        'frequency' => $course->frequency,
                        'default_price' => (float) $course->default_price,

                        'instructor' => $course->defaultInstructor
                            ? [
                                'id' => $course->defaultInstructor->id,
                                'name' => "{$course->defaultInstructor->first_name} {$course->defaultInstructor->last_name}",
                            ]
                            : null,

                        'lessons' => $courseLessons,
                        'first_lesson' => $firstLesson,
                        'has_english' => $hasEnglish,
                    ];
                })
                ->values();

            if ($matchingCourses->isNotEmpty()) {
                $categories[] = [
                    'key' => $meta['key'],
                    'title' => $meta['title'],
                    'description' => $meta['description'],
                    'stages' => $matchingCourses,
                ];
            }
        }

        // 5. Données de réservation du compte connecté.
        $attendees = $user
            ? $user->attendees()->orderBy('first_name')->get()
            : [];

        $activeAbsences = $user
            ? Absence::query()
                ->where('active', true)
                ->whereHas('enrollment.module', function ($query) use ($user) {
                    $query
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
                })
                ->get()
            : [];

        return Inertia::render('Front/Stages', [
            'categories' => $categories,
            'attendees' => $attendees,
            'activeAbsences' => $activeAbsences,
        ]);
    }

    /**
     * Transforme le chemin enregistré en URL publique.
     */
    private function coverImageUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        // Anciennes URL absolues.
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        // Anciens chemins publics, par exemple /images/stages/photo.jpg.
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Couvertures enregistrées par LessonGeneratorService.
        if (str_starts_with($path, 'courses/covers/')) {
            return Storage::disk('public')->url($path);
        }

        // Compatibilité avec les anciens chemins relatifs au dossier public.
        return '/' . $path;
    }
}
