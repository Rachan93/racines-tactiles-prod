<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LessonAvailabilityService
{
    /**
     * Récupère la liste des leçons avec les agrégations SQL et filtres avancés.
     */
    public function getLessonsForCalendar(array $filters = [], ?User $user = null): Collection
    {
        $today = Carbon::today()->toDateString();

        $query = Lesson::query()
            ->with([
                'course.type',
                'course.defaultInstructor',
                'overrideInstructor',
            ])
            ->where('is_cancelled', false)
            // 1. Sécurité : Uniquement les cours parents actifs et publiés
            ->whereHas('course', function (Builder $q) {
                $q->where('is_active', true);
            })
            // 2. Sécurité : Aucune séance passée
            ->where('date', '>=', $today);

        // 3. Eager-loading conditionnel des inscriptions de l'utilisateur et de ses invités (Zero N+1)
        if ($user) {
            $attendeeIds = $user->attendees()->pluck('id')->toArray();

            $query->with(['enrollments' => function ($q) use ($user, $attendeeIds) {
                $q->where(function ($sub) use ($user, $attendeeIds) {
                    $sub->whereHas('module', function ($m) use ($user) {
                        $m->where('participant_type', User::class)
                            ->where('participant_id', $user->id);
                    })->orWhereHas('module', function ($m) use ($attendeeIds) {
                        $m->where('participant_type', 'App\Models\Attendee')
                            ->whereIn('participant_id', $attendeeIds);
                    });
                })->with(['module.participant']);
            }]);
        }

        // Application des filtres de date et de type
        $this->applyFilters($query, $filters);

        // 4. Agrégations SQL précises (Capacités & Places)
        $query->withCount([
            // Sièges réguliers occupés (Verrouillés sur toute la série)
            'enrollments as regular_handbuilding_seats' => function ($q) {
                $q->where('spot_type', 'handbuilding')
                    ->where('enrollment_type', 'regular')
                    ->whereIn('status', ['registered', 'absent']);
            },
            'enrollments as regular_wheel_seats' => function ($q) {
                $q->where('spot_type', 'wheel')
                    ->where('enrollment_type', 'regular')
                    ->whereIn('status', ['registered', 'absent']);
            },
            'enrollments as regular_total_seats' => function ($q) {
                $q->where('enrollment_type', 'regular')
                    ->whereIn('status', ['registered', 'absent']);
            },

            // Trous d'absences spécifiques à cette séance
            'enrollments as absent_handbuilding_count' => function ($q) {
                $q->where('spot_type', 'handbuilding')
                    ->where('enrollment_type', 'regular')
                    ->where('status', 'absent');
            },

            'enrollments as absent_wheel_count' => function ($q) {
                $q->where('spot_type', 'wheel')
                    ->where('enrollment_type', 'regular')
                    ->where('status', 'absent');
            },

            // Rattrapages déjà positionnés sur cette séance
            'enrollments as makeup_handbuilding_count' => function ($q) {
                $q->where('spot_type', 'handbuilding')
                    ->where('enrollment_type', 'makeup')
                    ->where('status', 'registered');
            },
            'enrollments as makeup_wheel_count' => function ($q) {
                $q->where('spot_type', 'wheel')
                    ->where('enrollment_type', 'makeup')
                    ->where('status', 'registered');
            },

            // Occupation physique réelle (pour l'affichage X / Max)
            'enrollments as physical_handbuilding_count' => function ($q) {
                $q->where('spot_type', 'handbuilding')
                    ->where('status', 'registered');
            },
            'enrollments as physical_wheel_count' => function ($q) {
                $q->where('spot_type', 'wheel')
                    ->where('status', 'registered');
            },
        ]);

        $lessons = $query->get();

        // 5. Post-filtrage des disponibilités calculées (Filtres spécifiques)
        return $this->applyDynamicAvailabilityFilters($lessons, $filters);
    }

    /**
     * Retourne la date de la prochaine séance disponible respectant les filtres.
     */
    public function getNextLessonDate(array $filters = []): ?string
    {
        $fromDate = !empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->startOfDay()
            : Carbon::today();

        // On ne va jamais chercher dans le passé
        if ($fromDate->lt(Carbon::today())) {
            $fromDate = Carbon::today();
        }

        $query = Lesson::query()
            ->where('is_cancelled', false)
            ->where('date', '>=', $fromDate)
            ->whereHas('course', function (Builder $q) {
                $q->where('is_active', true);
            });

        $this->applyCourseFilters($query, $filters);

        $date = $query->orderBy('date', 'asc')->value('date');

        return $date ? Carbon::parse($date)->toDateString() : null;
    }

    /**
     * Applique les filtres de base sur la requête.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $this->applyCourseFilters($query, $filters);

        $today = Carbon::today()->startOfDay();

        // Plage de dates avec verrouillage sur le futur
        if (!empty($filters['start_date'])) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            $query->where('date', '>=', $startDate->lt($today) ? $today : $startDate);
        } else {
            $query->where('date', '>=', $today);
        }

        if (!empty($filters['end_date'])) {
            $query->where('date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        } else {
            $query->where('date', '<=', Carbon::now()->addMonths(4)->endOfDay());
        }
    }

    /**
     * Filtre par type ou par cours.
     */
    protected function applyCourseFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['type_id'])) {
            $query->whereHas('course', function ($q) use ($filters) {
                $q->where('type_id', $filters['type_id']);
            });
        }

        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }
    }

    /**
     * Filtrage dynamique des collections en fonction des capacités calculées.
     */
    protected function applyDynamicAvailabilityFilters(Collection $lessons, array $filters): Collection
    {
        $spotTypeFilter = $filters['spot_type'] ?? null; // 'wheel', 'handbuilding'
        $hideFull = filter_var($filters['hide_full'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $onlyMakeups = filter_var($filters['only_makeups'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $lessons->filter(function (Lesson $lesson) use ($spotTypeFilter, $hideFull, $onlyMakeups) {
            $maxRoom = 10;
            $maxWheel = 8;
            $maxHandbuilding = 4;

            $regHand = $lesson->regular_handbuilding_seats ?? 0;
            $regWheel = $lesson->regular_wheel_seats ?? 0;
            $regTotal = $lesson->regular_total_seats ?? ($regHand + $regWheel);

            $roomRemaining = max(0, $maxRoom - $regTotal);
            $spotsHand = max(0, min($maxHandbuilding - $regHand, $roomRemaining));
            $spotsWheel = max(0, min($maxWheel - $regWheel, $roomRemaining));
            $totalStandard = $spotsHand + $spotsWheel;

            $lessonDate = Carbon::parse($lesson->date)->startOfDay();
            $isWithinJ6 = $lessonDate->gte(Carbon::today()) && $lessonDate->lte(Carbon::today()->addDays(6));

            $absentHand = max(0, ($lesson->absent_handbuilding_count ?? 0) - ($lesson->makeup_handbuilding_count ?? 0));
            $absentWheel = max(0, ($lesson->absent_wheel_count ?? 0) - ($lesson->makeup_wheel_count ?? 0));

            $makeupsHand = $isWithinJ6 ? ($absentHand + $spotsHand) : $absentHand;
            $makeupsWheel = $isWithinJ6 ? ($absentWheel + $spotsWheel) : $absentWheel;
            $totalMakeups = $makeupsHand + $makeupsWheel;

            // Filtre "Masquer les cours complets"
            if ($hideFull && $totalStandard <= 0 && $totalMakeups <= 0) {
                return false;
            }

            // Filtre "Uniquement séances avec rattrapages disponibles"
            if ($onlyMakeups && $totalMakeups <= 0) {
                return false;
            }

            // Filtre par type de poste (Tour vs Modelage)
            if ($spotTypeFilter === 'wheel' && $spotsWheel <= 0 && $makeupsWheel <= 0) {
                return false;
            }

            if ($spotTypeFilter === 'handbuilding' && $spotsHand <= 0 && $makeupsHand <= 0) {
                return false;
            }

            return true;
        })->values();
    }
}
