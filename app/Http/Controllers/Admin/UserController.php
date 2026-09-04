<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserIndexRequest;
use App\Jobs\SendBroadcastEmailJob;
use App\Mail\AdminBroadcastMail;
use App\Models\Attendee;
use App\Models\Course;
use App\Models\User;
use App\QueryBuilders\PeopleQueryBuilder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Répertoire des membres et invités avec pagination et filtres multi-critères.
     */
    public function index(UserIndexRequest $request, PeopleQueryBuilder $queryBuilder): Response
    {
        $filters = $request->validatedWithDefaults();

        // 1. Pagination des membres (users)
        $usersPaginator = $queryBuilder->getUsersQuery($filters)
            ->paginate($filters['users_perPage'], ['*'], 'users_page', $filters['users_page'])
            ->withQueryString();

        // 2. Pagination des invités (attendees)
        $attendeesPaginator = $queryBuilder->getAttendeesQuery($filters)
            ->paginate($filters['attendees_perPage'], ['*'], 'attendees_page', $filters['attendees_page'])
            ->withQueryString();

        // 3. Liste des cours pour le filtre
        $courses = Course::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // 4. Formatage léger pour Vue 3
        $users = $usersPaginator->through(function (User $user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'address' => $user->address,
                'locality' => $user->locality,
                'postal_code' => $user->postal_code,
                'birthday' => $user->birthday ? Carbon::parse($user->birthday)->format('Y-m-d') : null,
                'birthday_formatted' => $user->birthday ? Carbon::parse($user->birthday)->translatedFormat('d F Y') : '-',
                'billing' => (bool) $user->billing,
                'company_name' => $user->company_name,
                'company_address' => $user->company_address,
                'company_locality' => $user->company_locality,
                'company_postal_code' => $user->company_postal_code,
                'vat_number' => $user->vat_number,
                'created_at' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                'created_at_formatted' => $user->created_at ? $user->created_at->translatedFormat('d M Y') : '-',
                'modules_count' => $user->modules_count ?? 0,
                'attendees_count' => $user->attendees_count ?? 0,
                'attendees' => $user->attendees->map(fn($a) => [
                    'id' => $a->id,
                    'first_name' => $a->first_name,
                    'last_name' => $a->last_name,
                    'full_name' => "{$a->first_name} {$a->last_name}",
                    'birthday' => $a->birthday ? Carbon::parse($a->birthday)->format('Y-m-d') : null,
                    'birthday_formatted' => $a->birthday ? Carbon::parse($a->birthday)->translatedFormat('d F Y') : null,
                    'created_at' => $a->created_at ? $a->created_at->format('Y-m-d') : null,
                    'created_at_formatted' => $a->created_at ? $a->created_at->translatedFormat('d M Y') : '-',
                ]),
            ];
        });

        $attendees = $attendeesPaginator->through(function (Attendee $attendee) {
            return [
                'id' => $attendee->id,
                'first_name' => $attendee->first_name,
                'last_name' => $attendee->last_name,
                'full_name' => "{$attendee->first_name} {$attendee->last_name}",
                'birthday' => $attendee->birthday ? Carbon::parse($attendee->birthday)->format('Y-m-d') : null,
                'birthday_formatted' => $attendee->birthday ? Carbon::parse($attendee->birthday)->translatedFormat('d F Y') : '-',
                'created_at' => $attendee->created_at ? $attendee->created_at->format('Y-m-d') : null,
                'created_at_formatted' => $attendee->created_at ? $attendee->created_at->translatedFormat('d M Y') : '-',
                'modules_count' => $attendee->modules_count ?? 0,
                'user' => $attendee->user ? [
                    'id' => $attendee->user->id,
                    'first_name' => $attendee->user->first_name,
                    'last_name' => $attendee->user->last_name,
                    'full_name' => "{$attendee->user->first_name} {$attendee->user->last_name}",
                    'email' => $attendee->user->email,
                    'phone_number' => $attendee->user->phone_number,
                ] : null,
            ];
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->items(),
            'usersPagination' => [
                'page' => $usersPaginator->currentPage(),
                'perPage' => $usersPaginator->perPage(),
                'total' => $usersPaginator->total(),
                'lastPage' => $usersPaginator->lastPage(),
            ],
            'attendees' => $attendees->items(),
            'attendeesPagination' => [
                'page' => $attendeesPaginator->currentPage(),
                'perPage' => $attendeesPaginator->perPage(),
                'total' => $attendeesPaginator->total(),
                'lastPage' => $attendeesPaginator->lastPage(),
            ],
            'courses' => $courses,
            'filters' => $filters,
        ]);
    }

    /**
     * Recherche d'utilisateurs pour l'autocomplétion du sélecteur de destinataires.
     */
    public function searchRecipients(Request $request): JsonResponse
    {
        $query = trim($request->query('query', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::query()
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone_number'])
            ->map(fn($u) => [
                'id' => $u->id,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'full_name' => "{$u->first_name} {$u->last_name}",
                'email' => $u->email,
                'phone_number' => $u->phone_number,
            ]);

        return response()->json($users);
    }

    /**
     * Vérifie par lot si une liste d'adresses e-mails correspond à des membres inscrits.
     */
    public function checkRecipients(Request $request): JsonResponse
    {
        $emails = (array) $request->input('emails', []);

        if (empty($emails)) {
            return response()->json([]);
        }

        $users = User::query()
            ->whereIn('email', $emails)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone_number'])
            ->map(fn($u) => [
                'id' => $u->id,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'full_name' => "{$u->first_name} {$u->last_name}",
                'email' => $u->email,
                'phone_number' => $u->phone_number,
            ]);

        return response()->json($users);
    }

    public function previewEmail(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],

        ]);

        $recipient = (object) [
            'first_name' => $validated['first_name'] ?? 'Sophie',
            'last_name' => $validated['last_name'] ?? 'Martin',
            'email' => $validated['email'] ?? 'sophie.martin@example.com',
        ];

        $mail = new AdminBroadcastMail(
            recipient: $recipient,
            subjectText: $validated['subject'],
            bodyText: $validated['body'],
            isTest: false,
            isPreview: true
        );

        return response($mail->render(), 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Envoi d'un e-mail de test à l'administrateur connecté.
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        /** @var \App\Models\User|null $admin */
        $admin = Auth::user();

        if (! $admin || ! $admin->email) {
            return response()->json(['error' => 'Administrateur introuvable.'], 422);
        }

        // On passe directement $validated['subject'], la classe AdminBroadcastMail gère le préfixe unique [TEST]
        Mail::to($admin->email)->send(new AdminBroadcastMail(
            recipient: $admin,
            subjectText: $validated['subject'],
            bodyText: $validated['body'],
            isTest: true
        ));

        return response()->json([
            'message' => "E-mail de test envoyé à {$admin->email}.",
        ]);
    }

    /**
     * Envoi d'e-mails groupés mis en file d'attente (Queue / Job).
     */
    /**
     * Mise en file d'attente des e-mails groupés.
     * Chaque destinataire reçoit son propre job.
     */
    public function sendBulkEmail(
        Request $request,
        PeopleQueryBuilder $queryBuilder
    ): JsonResponse {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],

            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:users,id'],

            'custom_emails' => ['nullable', 'array'],
            'custom_emails.*' => ['email'],

            'select_all_matching' => ['nullable', 'boolean'],
            'filters' => ['nullable', 'array'],
        ]);

        $selectAll = filter_var(
            $validated['select_all_matching'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $recipientIds = $validated['recipient_ids'] ?? [];
        $customEmails = $validated['custom_emails'] ?? [];

        /*
    |--------------------------------------------------------------------------
    | Membres sélectionnés
    |--------------------------------------------------------------------------
    */

        if ($selectAll) {
            $filters = $validated['filters'] ?? [];

            $users = $queryBuilder
                ->getUsersQuery($filters)
                ->get([
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                ]);
        } else {
            $users = User::query()
                ->whereIn('id', $recipientIds)
                ->get([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Création de la liste finale
    |--------------------------------------------------------------------------
    */

        $recipients = collect();

        foreach ($users as $user) {
            if (
                empty($user->email) ||
                ! filter_var($user->email, FILTER_VALIDATE_EMAIL)
            ) {
                continue;
            }

            $recipients->push([
                'email' => strtolower(trim($user->email)),
                'first_name' => $user->first_name ?? 'Membre',
                'last_name' => $user->last_name ?? '',
            ]);
        }

        foreach ($customEmails as $email) {
            $cleanEmail = strtolower(trim((string) $email));

            if (
                empty($cleanEmail) ||
                ! filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)
            ) {
                continue;
            }

            $recipients->push([
                'email' => $cleanEmail,
                'first_name' => explode('@', $cleanEmail)[0],
                'last_name' => '',
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Suppression des doublons
    |--------------------------------------------------------------------------
    */

        $recipients = $recipients
            ->unique('email')
            ->values();

        if ($recipients->isEmpty()) {
            return response()->json([
                'error' => 'Aucun destinataire valide sélectionné.',
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Dispatch
    |--------------------------------------------------------------------------
    |
    | Décalage de 2 secondes entre chaque job pour éviter de bombarder
    | le serveur SMTP.
    |
    */

        foreach ($recipients as $index => $recipient) {
            SendBroadcastEmailJob::dispatch(
                email: $recipient['email'],
                firstName: $recipient['first_name'],
                lastName: $recipient['last_name'],
                subject: $validated['subject'],
                body: $validated['body'],
            )->delay(
                now()->addSeconds(
                    $index * config('racines.broadcast_email_delay')
                )
            );
        }

        $count = $recipients->count();

        return response()->json([
            'message' => "{$count} e-mail(s) ont été mis en file d'envoi.",
            'count' => $count,
        ]);
    }

    /**
     * Export CSV en streaming mémoire (O(1) RAM).
     */
    public function exportCsv(Request $request, PeopleQueryBuilder $queryBuilder): StreamedResponse
    {
        $type = $request->query('type', 'users');
        $selectedIds = array_filter((array) $request->input('selected_ids', []));
        $selectAll = filter_var($request->input('select_all_matching', false), FILTER_VALIDATE_BOOLEAN);
        $filters = $request->input('filters', []);

        return response()->streamDownload(function () use ($type, $selectedIds, $selectAll, $filters, $queryBuilder) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour compatibilité Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($type === 'users') {
                fputcsv($handle, [
                    'ID',
                    'Nom',
                    'Prénom',
                    'Email',
                    'Téléphone',
                    'Adresse',
                    'Code postal',
                    'Localité',
                    'Date Naissance',
                    'Société',
                    'Adresse Société',
                    'TVA',
                    'Modules',
                    'Invités',
                    'Date Inscription'
                ], ';');

                $query = $selectAll
                    ? $queryBuilder->getUsersQuery($filters)
                    : User::whereIn('id', $selectedIds)->withCount(['modules', 'attendees']);

                $query->chunk(200, function ($users) use ($handle) {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->last_name,
                            $user->first_name,
                            $user->email,
                            $user->phone_number ?? '-',
                            $user->address ?? '-',
                            $user->postal_code ?? '-',
                            $user->locality ?? '-',
                            $user->birthday ? Carbon::parse($user->birthday)->format('d/m/Y') : '-',
                            $user->company_name ?? '-',
                            $user->company_address ? "{$user->company_address}, {$user->company_postal_code} {$user->company_locality}" : '-',
                            $user->vat_number ?? '-',
                            $user->modules_count ?? 0,
                            $user->attendees_count ?? 0,
                            $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-',
                        ], ';');
                    }
                });
            } else {
                fputcsv($handle, ['ID', 'Nom', 'Prénom', 'Date Naissance', 'Responsable', 'Email Responsable', 'Téléphone Responsable', 'Date Inscription'], ';');

                $query = $selectAll
                    ? $queryBuilder->getAttendeesQuery($filters)
                    : Attendee::whereIn('id', $selectedIds)->with('user');

                $query->chunk(200, function ($attendees) use ($handle) {
                    foreach ($attendees as $attendee) {
                        fputcsv($handle, [
                            $attendee->id,
                            $attendee->last_name,
                            $attendee->first_name,
                            $attendee->birthday ? Carbon::parse($attendee->birthday)->format('d/m/Y') : '-',
                            $attendee->user ? "{$attendee->user->first_name} {$attendee->user->last_name}" : '-',
                            $attendee->user?->email ?? '-',
                            $attendee->user?->phone_number ?? '-',
                            $attendee->created_at ? $attendee->created_at->format('d/m/Y H:i') : '-',
                        ], ';');
                    }
                });
            }

            fclose($handle);
        }, ($type === 'users' ? 'membres' : 'invites') . '-' . now()->format('Y-m-d') . '.csv');
    }

    /**
     * Suppression groupée de membres ou d'invités.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:users,attendees'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        if ($validated['type'] === 'users') {
            User::whereIn('id', $validated['ids'])->delete();
        } else {
            Attendee::whereIn('id', $validated['ids'])->delete();
        }

        return redirect()->back();
    }

    /**
     * Fiche détaillée d'un membre.
     */
    /**
     * Fiche détaillée d'un membre.
     *
     * Affiche :
     * - les informations du titulaire ;
     * - ses invités ;
     * - ses propres modules ;
     * - les modules de ses invités ;
     * - les séances et absences de chaque module.
     */
    public function show(User $user): Response
    {
        $today = now()->startOfDay();

        /*
    |--------------------------------------------------------------------------
    | Relations nécessaires à chaque module
    |--------------------------------------------------------------------------
    */

        $moduleRelations = [
            'type:id,name',

            'enrollments' => fn($query) => $query->with([
                'lesson.course.type',
                'absences',
                'replacesAbsence.enrollment.lesson.course',
            ]),
        ];

        /*
    |--------------------------------------------------------------------------
    | Chargement du compte complet
    |--------------------------------------------------------------------------
    */

        $user->load([
            'modules' => fn($query) => $query
                ->with($moduleRelations)
                ->orderByDesc('purchase_date'),

            'attendees' => fn($query) => $query
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->with([
                    'modules' => fn($moduleQuery) => $moduleQuery
                        ->with($moduleRelations)
                        ->orderByDesc('purchase_date'),
                ]),
        ]);

        /*
    |--------------------------------------------------------------------------
    | Regroupement de tous les modules du compte
    |--------------------------------------------------------------------------
    |
    | On garde les modules du titulaire ET ceux de ses invités.
    |
    */

        $moduleEntries = collect();

        foreach ($user->modules as $module) {
            $moduleEntries->push([
                'module' => $module,
                'participant' => [
                    'id' => $user->id,
                    'type' => 'user',
                    'name' => trim("{$user->first_name} {$user->last_name}"),
                    'label' => 'Titulaire',
                ],
            ]);
        }

        foreach ($user->attendees as $attendee) {
            foreach ($attendee->modules as $module) {
                $moduleEntries->push([
                    'module' => $module,
                    'participant' => [
                        'id' => $attendee->id,
                        'type' => 'attendee',
                        'name' => trim("{$attendee->first_name} {$attendee->last_name}"),
                        'label' => 'Invité',
                    ],
                ]);
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Formatage des modules
    |--------------------------------------------------------------------------
    */

        $modules = $moduleEntries
            ->sortByDesc(
                fn($entry) =>
                $entry['module']->purchase_date?->timestamp ?? 0
            )
            ->values()
            ->map(function ($entry) use ($today) {
                /** @var \App\Models\Module $module */
                $module = $entry['module'];

                /*
             * Tri chronologique des inscriptions.
             */
                $sortedEnrollments = $module->enrollments
                    ->sortBy(function ($enrollment) {
                        $lesson = $enrollment->lesson;

                        if (! $lesson || ! $lesson->date) {
                            return '9999-12-31 23:59:59';
                        }

                        $date = Carbon::parse($lesson->date)
                            ->format('Y-m-d');

                        $time = (string) (
                            $lesson->effective_start_time ?? '23:59:59'
                        );

                        return "{$date} {$time}";
                    })
                    ->values();

                /*
             * Séances régulières uniquement pour la progression.
             */
                $regularEnrollments = $sortedEnrollments
                    ->where('enrollment_type', 'regular');

                $completedLessons = $regularEnrollments
                    ->filter(function ($enrollment) use ($today) {
                        if (! $enrollment->lesson?->date) {
                            return false;
                        }

                        return Carbon::parse($enrollment->lesson->date)
                            ->startOfDay()
                            ->lt($today);
                    })
                    ->count();

                /*
             * Rattrapages utilisés.
             */
                $makeupsUsed = $sortedEnrollments
                    ->where('enrollment_type', 'makeup')
                    ->whereIn('status', ['registered', 'absent'])
                    ->count();

                $maxMakeups = $module->max_makeups_allowed;

                $remainingMakeups = max(
                    0,
                    $maxMakeups - $makeupsUsed
                );

                /*
             * Séances à venir encore actives.
             */
                $upcomingLessons = $sortedEnrollments
                    ->filter(function ($enrollment) use ($today) {
                        if (
                            ! $enrollment->lesson?->date ||
                            $enrollment->status !== 'registered'
                        ) {
                            return false;
                        }

                        return Carbon::parse($enrollment->lesson->date)
                            ->startOfDay()
                            ->gte($today);
                    })
                    ->count();

                /*
             * Numérotation :
             * les rattrapages ne prennent PAS un numéro de
             * séance régulière.
             */
                $regularSequence = 0;

                $formattedEnrollments = $sortedEnrollments
                    ->map(function ($enrollment) use (
                        &$regularSequence,
                        $today
                    ) {
                        $lesson = $enrollment->lesson;

                        if ($enrollment->enrollment_type === 'regular') {
                            $regularSequence++;
                        }

                        $lessonDate = $lesson?->date
                            ? Carbon::parse($lesson->date)
                            : null;

                        $activeAbsence = $enrollment->absences
                            ->first(
                                fn($absence) =>
                                $absence->active &&
                                    $absence->cancellation_date === null
                            );

                        $replacedAbsence =
                            $enrollment->replacesAbsence;

                        return [
                            'id' => $enrollment->id,

                            'sequence_number' =>
                            $enrollment->enrollment_type === 'regular'
                                ? $regularSequence
                                : null,

                            'status' => $enrollment->status,

                            'enrollment_type' =>
                            $enrollment->enrollment_type,

                            'spot_type' => $enrollment->spot_type,

                            'is_past' => $lessonDate
                                ? $lessonDate
                                ->copy()
                                ->startOfDay()
                                ->lt($today)
                                : false,

                            'lesson' => [
                                'id' => $lesson?->id,

                                'date' => $lessonDate
                                    ? $lessonDate->format('Y-m-d')
                                    : null,

                                'date_formatted' => $lessonDate
                                    ? ucfirst(
                                        $lessonDate
                                            ->locale('fr')
                                            ->translatedFormat('D d M Y')
                                    )
                                    : '-',

                                'start_time' => $lesson
                                    ? substr(
                                        (string) $lesson->effective_start_time,
                                        0,
                                        5
                                    )
                                    : '-',

                                'end_time' => $lesson
                                    ? substr(
                                        (string) $lesson->effective_end_time,
                                        0,
                                        5
                                    )
                                    : '-',

                                'course_name' =>
                                $lesson?->course?->name ?? 'Cours supprimé',

                                'type_name' =>
                                $lesson?->course?->type?->name ?? 'Général',

                                'is_cancelled' =>
                                (bool) ($lesson?->is_cancelled ?? false),
                            ],

                            'absence' => $activeAbsence
                                ? [
                                    'id' => $activeAbsence->id,
                                    'active' => true,
                                    'notification_date' =>
                                    $activeAbsence->notification_date
                                        ? Carbon::parse(
                                            $activeAbsence->notification_date
                                        )->translatedFormat('d M Y H:i')
                                        : null,
                                ]
                                : null,

                            'replaces' => $replacedAbsence
                                ? [
                                    'id' => $replacedAbsence->id,

                                    'course_name' =>
                                    $replacedAbsence
                                        ->enrollment
                                        ?->lesson
                                        ?->course
                                        ?->name,

                                    'date' =>
                                    $replacedAbsence
                                        ->enrollment
                                        ?->lesson
                                        ?->date
                                        ? Carbon::parse(
                                            $replacedAbsence
                                                ->enrollment
                                                ->lesson
                                                ->date
                                        )->translatedFormat('d M Y')
                                        : null,
                                ]
                                : null,
                        ];
                    });

                return [
                    'id' => $module->id,

                    'participant' => $entry['participant'],

                    'type' => [
                        'id' => $module->type?->id,
                        'name' => $module->type?->name ?? 'Général',
                    ],

                    'is_active' => (bool) $module->is_active,

                    'total_lessons' => (int) $module->total_lessons,

                    'completed_lessons' => $completedLessons,

                    'upcoming_lessons' => $upcomingLessons,

                    'paid_price' => (float) $module->paid_price,

                    'purchase_date' => $module->purchase_date
                        ? $module->purchase_date
                        ->locale('fr')
                        ->translatedFormat('d M Y')
                        : '-',

                    'expiration_date' => $module->expiration_date
                        ? $module->expiration_date
                        ->locale('fr')
                        ->translatedFormat('d M Y')
                        : null,

                    'max_makeups_allowed' => $maxMakeups,

                    'makeups_used_count' => $makeupsUsed,

                    'remaining_makeups' => $remainingMakeups,

                    'absences_count' => $sortedEnrollments
                        ->where('status', 'absent')
                        ->count(),

                    'enrollments' => $formattedEnrollments,
                ];
            });

        /*
    |--------------------------------------------------------------------------
    | Invités
    |--------------------------------------------------------------------------
    */

        $attendees = $user->attendees
            ->map(function (Attendee $attendee) {
                return [
                    'id' => $attendee->id,

                    'first_name' => $attendee->first_name,
                    'last_name' => $attendee->last_name,

                    'full_name' => trim(
                        "{$attendee->first_name} {$attendee->last_name}"
                    ),

                    'birthday' => $attendee->birthday
                        ? Carbon::parse($attendee->birthday)
                        ->locale('fr')
                        ->translatedFormat('d F Y')
                        : null,

                    'created_at' => $attendee->created_at
                        ? $attendee->created_at
                        ->locale('fr')
                        ->translatedFormat('d M Y')
                        : null,

                    'modules_count' => $attendee->modules->count(),

                    'active_modules_count' => $attendee->modules
                        ->where('is_active', true)
                        ->count(),
                ];
            })
            ->values();

        /*
    |--------------------------------------------------------------------------
    | Statistiques du compte
    |--------------------------------------------------------------------------
    */

        $upcomingEnrollmentsCount = $modules->sum(
            fn($module) =>
            collect($module['enrollments'])
                ->where('is_past', false)
                ->where('status', 'registered')
                ->count()
        );

        /*
    |--------------------------------------------------------------------------
    | Réponse Inertia
    |--------------------------------------------------------------------------
    */

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,

                'first_name' => $user->first_name,
                'last_name' => $user->last_name,

                'full_name' => trim(
                    "{$user->first_name} {$user->last_name}"
                ),

                'email' => $user->email,

                'email_verified' =>
                $user->email_verified_at !== null,

                'phone_number' => $user->phone_number,

                'birthday' => $user->birthday
                    ? Carbon::parse($user->birthday)
                    ->locale('fr')
                    ->translatedFormat('d F Y')
                    : null,

                'address' => $user->address,
                'postal_code' => $user->postal_code,
                'locality' => $user->locality,

                'billing' => (bool) $user->billing,

                'company_name' => $user->company_name,
                'company_address' => $user->company_address,
                'company_postal_code' => $user->company_postal_code,
                'company_locality' => $user->company_locality,
                'vat_number' => $user->vat_number,

                'created_at' => $user->created_at
                    ? $user->created_at
                    ->locale('fr')
                    ->translatedFormat('d F Y')
                    : null,

                'updated_at' => $user->updated_at
                    ? $user->updated_at
                    ->locale('fr')
                    ->translatedFormat('d F Y à H:i')
                    : null,
            ],

            'stats' => [
                'modules_count' => $modules->count(),

                'active_modules_count' =>
                $modules
                    ->where('is_active', true)
                    ->count(),

                'attendees_count' => $attendees->count(),

                'upcoming_enrollments_count' =>
                $upcomingEnrollmentsCount,
            ],

            'attendees' => $attendees,

            'modules' => $modules,
        ]);
    }
}
