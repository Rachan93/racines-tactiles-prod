<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Attendee;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now = Carbon::now();
        $selectedPeriod = $request->query('period', '6m');

        // -------------------------------------------------------------
        // 1. KPIs PRINCIPAUX
        // -------------------------------------------------------------
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // A. Modules Actifs & Croissance
        $activeModulesCount = Module::where('is_active', true)->count();
        $newModulesThisMonth = Module::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $newModulesLastMonth = Module::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $modulesGrowth = $newModulesLastMonth > 0
            ? round((($newModulesThisMonth - $newModulesLastMonth) / $newModulesLastMonth) * 100)
            : null;

        // B. Total Communauté (Membres + Accompagnants)
        $totalUsers = User::count();
        $totalAttendees = Attendee::count();
        $newUsersThisMonth = User::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        // C. Taux d'occupation sur les 7 prochains jours
        $next7DaysLessons = Lesson::with(['course', 'enrollments' => function ($q) {
            $q->where('status', 'registered');
        }])
            ->whereBetween('date', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])
            ->where('is_cancelled', false)
            ->get();

        $totalSpotsAvailable = $next7DaysLessons->sum(fn($l) => $l->effective_spots_max_wheel + $l->effective_spots_max_handbuilding);
        $totalSpotsBooked = $next7DaysLessons->sum(fn($l) => $l->enrollments->count());
        $occupancyRate = $totalSpotsAvailable > 0 ? round(($totalSpotsBooked / $totalSpotsAvailable) * 100) : 0;

        // D. Crédits de rattrapage encore disponibles
        $availableMakeupCredits = Absence::where('active', true)
            ->whereNull('cancellation_date')
            ->whereDoesntHave(
                'replacements',
                fn($q) => $q->where('status', 'registered')
            )
            ->count();
        // -------------------------------------------------------------
        // 2. GRAPHIQUE D'ÉVOLUTION : Inscrits vs Modules réservés
        // -------------------------------------------------------------
        $chartData = $this->buildEvolutionData($selectedPeriod);

        // -------------------------------------------------------------
        // 3. DONUT CHART : Répartition des modules par type de cours
        // -------------------------------------------------------------
        $types = Type::withCount('modules')->get();
        $donutPalette = ['var(--chart-1, #0284c7)', 'var(--chart-2, #ea580c)', 'var(--chart-3, #8b5cf6)', 'var(--chart-4, #10b981)', 'var(--chart-5, #f59e0b)'];

        $moduleTypesDistribution = $types->map(function ($type, $index) use ($donutPalette) {
            return [
                'id' => $type->id,
                'label' => $type->name,
                'value' => $type->modules_count,
                'color' => $donutPalette[$index % count($donutPalette)],
            ];
        })->filter(fn($t) => $t['value'] > 0)->values();

        // -------------------------------------------------------------
        // 4. DERNIÈRES RÉSERVATIONS DE MODULES (5 récents)
        // -------------------------------------------------------------
        $recentModules = Module::with(['type', 'participant'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($module) {
                $participant = $module->participant;

                $participantName = $participant
                    ? $participant->first_name . ' ' . $participant->last_name
                    : 'Inconnu';

                $isAttendee = $participant instanceof Attendee;

                /*
         * Si c'est un invité, on ouvre le compte du membre parent.
         * Sinon le participant est directement le membre.
         */
                $ownerUserId = $isAttendee
                    ? $participant?->user_id
                    : $participant?->id;

                return [
                    'id' => $module->id,

                    'owner_user_id' => $ownerUserId,

                    'participant_name' => $participantName,

                    'participant_type' => $isAttendee
                        ? 'Invité'
                        : 'Membre',

                    'type_name' => $module->type->name ?? 'Général',

                    'total_lessons' => $module->total_lessons,

                    'is_active' => $module->is_active,

                    'created_at' => $module->created_at
                        ? $module->created_at->diffForHumans()
                        : '-',
                ];
            });

        // -------------------------------------------------------------
        // 5. DERNIERS UTILISATEURS INSCRITS SUR LE SITE (5 récents)
        // -------------------------------------------------------------
        $recentUsers = User::latest()
            ->take(5)
            ->get([
                'id',
                'first_name',
                'last_name',
                'email',
                'created_at',
            ])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'created_at' => $user->created_at
                        ? $user->created_at->diffForHumans()
                        : '-',
                ];
            });

        // -------------------------------------------------------------
        // 6. PROCHAINES SÉANCES DU STUDIO (5 prochaines)
        // -------------------------------------------------------------
        $upcomingLessons = Lesson::with(['course.type', 'overrideInstructor', 'course.instructor', 'enrollments' => function ($q) {
            $q->where('status', 'registered');
        }])
            ->where('date', '>=', $now->toDateString())
            ->where('is_cancelled', false)
            ->orderBy('date', 'asc')
            ->take(5)
            ->get()
            ->map(function ($lesson) {
                $wheelBooked = $lesson->enrollments->where('spot_type', 'wheel')->count();
                $handbuildingBooked = $lesson->enrollments->where('spot_type', 'handbuilding')->count();
                $instructor = $lesson->effective_instructor;

                return [
                    'id' => $lesson->id,
                    'course_name' => $lesson->course->name ?? 'Séance studio',
                    'type_name' => $lesson->course->type->name ?? 'Général',
                    'date_formatted' => $lesson->date ? $lesson->date->translatedFormat('D d M') : '-',
                    'start_time' => substr($lesson->effective_start_time, 0, 5),
                    'end_time' => substr($lesson->effective_end_time, 0, 5),
                    'instructor_name' => $instructor ? $instructor->first_name . ' ' . $instructor->last_name : 'Non assigné',
                    'wheel' => [
                        'booked' => $wheelBooked,
                        'max' => $lesson->effective_spots_max_wheel,
                    ],
                    'handbuilding' => [
                        'booked' => $handbuildingBooked,
                        'max' => $lesson->effective_spots_max_handbuilding,
                    ],
                    'total_booked' => $wheelBooked + $handbuildingBooked,
                    'total_max' => $lesson->effective_spots_max_wheel + $lesson->effective_spots_max_handbuilding,
                ];
            });

        return Inertia::render('Admin/Dashboard/Index', [
            'kpis' => [
                'activeModules' => [
                    'value' => $activeModulesCount,
                    'newThisMonth' => $newModulesThisMonth,
                    'growth' => $modulesGrowth,
                ],
                'community' => [
                    'total' => $totalUsers + $totalAttendees,
                    'users' => $totalUsers,
                    'attendees' => $totalAttendees,
                    'newThisMonth' => $newUsersThisMonth,
                ],
                'occupancy' => [
                    'rate' => $occupancyRate,
                    'bookedSpots' => $totalSpotsBooked,
                    'totalSpots' => $totalSpotsAvailable,
                ],
                'absences' => [
                    'availableForMakeup' => $availableMakeupCredits,
                ],
            ],
            'selectedPeriod' => $selectedPeriod,
            'chartData' => $chartData,
            'moduleTypesDistribution' => $moduleTypesDistribution,
            'recentModules' => $recentModules,
            'recentUsers' => $recentUsers,
            'upcomingLessons' => $upcomingLessons,
        ]);
    }

    /**
     * Génère la série temporelle en fonction de la période demandée.
     */
    private function buildEvolutionData(string $period): array
    {
        $now = Carbon::now();
        $data = [];

        switch ($period) {
            case '1w': // 7 derniers jours (par jour)
                $periodRange = CarbonPeriod::create($now->copy()->subDays(6)->startOfDay(), '1 day', $now->copy()->endOfDay());
                foreach ($periodRange as $date) {
                    $start = $date->copy()->startOfDay();
                    $end = $date->copy()->endOfDay();
                    $data[] = [
                        'label' => ucfirst($date->translatedFormat('D d')),
                        'users' => User::whereBetween('created_at', [$start, $end])->count(),
                        'modules' => Module::whereBetween('created_at', [$start, $end])->count(),
                    ];
                }
                break;

            case '1m': // 30 derniers jours (par tranches de 5 jours ou semaines)
                $periodRange = CarbonPeriod::create($now->copy()->subDays(29)->startOfDay(), '5 days', $now->copy()->endOfDay());
                foreach ($periodRange as $date) {
                    $start = $date->copy()->startOfDay();
                    $end = $date->copy()->addDays(4)->endOfDay();
                    $data[] = [
                        'label' => $date->translatedFormat('d/m'),
                        'users' => User::whereBetween('created_at', [$start, $end])->count(),
                        'modules' => Module::whereBetween('created_at', [$start, $end])->count(),
                    ];
                }
                break;

            case '1y': // 12 derniers mois
                $periodRange = CarbonPeriod::create($now->copy()->subMonths(11)->startOfMonth(), '1 month', $now->copy()->startOfMonth());
                foreach ($periodRange as $date) {
                    $start = $date->copy()->startOfMonth();
                    $end = $date->copy()->endOfMonth();
                    $data[] = [
                        'label' => ucfirst($date->translatedFormat('M y')),
                        'users' => User::whereBetween('created_at', [$start, $end])->count(),
                        'modules' => Module::whereBetween('created_at', [$start, $end])->count(),
                    ];
                }
                break;

            case '3y': // 36 derniers mois (par trimestres)
                $periodRange = CarbonPeriod::create($now->copy()->subMonths(35)->startOfMonth(), '3 months', $now->copy()->startOfMonth());
                foreach ($periodRange as $date) {
                    $start = $date->copy()->startOfMonth();
                    $end = $date->copy()->addMonths(2)->endOfMonth();
                    $data[] = [
                        'label' => 'T' . ceil($date->month / 3) . ' ' . $date->format('y'),
                        'users' => User::whereBetween('created_at', [$start, $end])->count(),
                        'modules' => Module::whereBetween('created_at', [$start, $end])->count(),
                    ];
                }
                break;

            case 'all': // Depuis le début (par année ou semestre)
                $firstRecord = User::oldest()->first();
                $firstDate = $firstRecord ? Carbon::parse($firstRecord->created_at)->startOfYear() : $now->copy()->subYears(4)->startOfYear();
                $periodRange = CarbonPeriod::create($firstDate, '1 year', $now->copy()->startOfYear());
                foreach ($periodRange as $date) {
                    $start = $date->copy()->startOfYear();
                    $end = $date->copy()->endOfYear();
                    $data[] = [
                        'label' => $date->format('Y'),
                        'users' => User::whereBetween('created_at', [$start, $end])->count(),
                        'modules' => Module::whereBetween('created_at', [$start, $end])->count(),
                    ];
                }
                break;

            case '6m':
            default: // 6 derniers mois (par mois)
                $periodRange = CarbonPeriod::create($now->copy()->subMonths(5)->startOfMonth(), '1 month', $now->copy()->startOfMonth());
                foreach ($periodRange as $date) {
                    $start = $date->copy()->startOfMonth();
                    $end = $date->copy()->endOfMonth();
                    $data[] = [
                        'label' => ucfirst($date->translatedFormat('M')),
                        'users' => User::whereBetween('created_at', [$start, $end])->count(),
                        'modules' => Module::whereBetween('created_at', [$start, $end])->count(),
                    ];
                }
                break;
        }

        return $data;
    }
}
