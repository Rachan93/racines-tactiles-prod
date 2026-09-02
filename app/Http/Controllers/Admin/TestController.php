<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestBladePostStoreRequest;
use App\Http\Requests\TestBladeStoreRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Module;
use App\Models\Attendee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function index()
    {
        $users = User::with(['courses.lessons', 'lessons'])->get();

        $lessons = Lesson::with('course')->get();

        return Inertia::render('Admin/Test/Index', [
            'users' => $users,
            'lessons' => $lessons,
        ]);
    }

    public function redux(Request $request)
    {
        // Paramètres de pagination et de tri
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 10);
        $sortField = $request->input('sortField', 'id');
        $sortDirection = $request->input('sortDirection', 'asc');

        // Obtenir la collection paginée des utilisateurs
        $usersQuery = User::with([
            'modules' => function ($query) {
                $query->where('is_active', true);
            },
            'attendees.modules'
        ]);

        // Appliquer le tri
        if (in_array($sortField, ['id', 'first_name', 'last_name', 'email', 'phone_number'])) {
            $usersQuery->orderBy($sortField, $sortDirection);
        }

        // Traitement spécial pour les champs calculés ou relations
        if ($sortField === 'modules_count') {
            $usersQuery->withCount('modules')
                ->orderBy('modules_count', $sortDirection);
        }

        if ($sortField === 'attendees_count') {
            $usersQuery->withCount('attendees')
                ->orderBy('attendees_count', $sortDirection);
        }

        // Paginer les résultats
        $usersPagination = $usersQuery->paginate($perPage, ['*'], 'page', $page);

        // Extraire les données et les informations de pagination
        $paginationInfo = [
            'page' => $usersPagination->currentPage(),
            'perPage' => (int)$perPage, // Conversion explicite en entier
            'total' => $usersPagination->total(),
            'lastPage' => $usersPagination->lastPage(),
        ];

        $users = $usersPagination->items();

        // Récupération des autres données comme avant
        // ... [code existant pour récupérer d'autres données]

        // Récupération des cours actifs avec leurs séances
        $activeCourses = Course::with([
            'lessons' => function ($query) {
                $query->where('date', '>=', now())
                    ->orderBy('date');
            },
            'instructor'
        ])
            ->where('end_date', '>=', now())
            ->take(25)
            ->get();

        // Récupération des séances à venir
        $upcomingLessons = Lesson::with(['course', 'instructor'])
            ->where('date', '>=', now())
            ->where('is_cancelled', false)
            ->orderBy('date')
            ->take(25)
            ->get();

        // Modules actifs avec leurs participants
        $modules = Module::with([
            'participant',
            'lessons' => function ($query) {
                $query->orderBy('date');
            }
        ])
            ->where('is_active', true)
            ->take(25)
            ->get();

        // Participants (enfants/accompagnants)
        $attendees = Attendee::with(['user', 'modules'])
            ->take(25)
            ->get();

        // Statistiques générales pour le dashboard
        $stats = [
            'totalUsers' => User::count(),
            'totalCourses' => Course::count(),
            'activeCourses' => Course::where('end_date', '>=', now())->count(),
            'upcomingLessons' => Lesson::where('date', '>=', now())->where('is_cancelled', false)->count(),
            'totalModules' => Module::where('is_active', true)->count(),
            'totalAttendees' => Attendee::count(),
            'spotsAvailableHandbuilding' => $upcomingLessons->sum(function ($lesson) {
                return ($lesson->getSpotsMaxHandbuildingAttribute() - $lesson->spots_taken_handbuilding);
            }),
            'spotsAvailableWheel' => $upcomingLessons->sum(function ($lesson) {
                return ($lesson->getSpotsMaxWheelAttribute() - $lesson->spots_taken_wheel);
            }),
        ];



        return Inertia::render('Admin/Test/Redux', [
            'users' => $users, // Envoyer seulement le tableau d'utilisateurs (items)
            'activeCourses' => $activeCourses,
            'upcomingLessons' => $upcomingLessons,
            'modules' => $modules,
            'attendees' => $attendees,
            'stats' => $stats,
            'lastUpdate' => Carbon::now()->format('d/m/Y H:i:s'),
            // Ajouter les paramètres de pagination et de tri pour l'état initial
            'pagination' => $paginationInfo,
            'sorting' => [
                'field' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }
    public function blade(/*TestBladeStoreRequest $request*/)
    {
        // $validatedData = $request->validatedCustom();
        // Log::info('Données validées dans le controller:', $validatedData);
        return view('test.test');
    }

    public function bladePost(TestBladePostStoreRequest $request)
    {
        Log::info('✔️ Controller reached');
        Log::info('✔️ Validated:', $request->validated());
Log::info($request->valid());
        return back()->with('success', 'Validé');
    }
}
