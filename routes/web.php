<?php

use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\InstructorController as AdminInstructorController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\StudioClosureController as AdminStudioClosureController;
use App\Http\Controllers\Admin\TestController as AdminTestController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Front\AbsenceController;
use App\Http\Controllers\Front\AtelierController;
use App\Http\Controllers\Front\AttendeeController;
use App\Http\Controllers\Front\CalendrierController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\EnrollmentController;
use App\Http\Controllers\Front\FaqController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\MemberDashboardController;
use App\Http\Controllers\Front\MemberEnrollmentController;
use App\Http\Controllers\Front\MemberModuleController;
use App\Http\Controllers\Front\ModulePreviewController;
use App\Http\Controllers\Front\GalerieController;
use App\Http\Controllers\Front\StageController;
use App\Http\Middleware\CheckAdminRole;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Laravel\Jetstream\Http\Controllers\Inertia\CurrentUserController;
use Laravel\Jetstream\Http\Controllers\Inertia\OtherBrowserSessionsController;
use Laravel\Jetstream\Http\Controllers\Inertia\UserProfileController;

// =========================================================================
// PARAMÈTRES DU COMPTE / JETSTREAM
// =========================================================================

Route::get('/mentions-legales', function () {
    return Inertia::render('Legal/LegalNotice');
})->name('legal.notice');

Route::get('/conditions-generales-de-vente', function () {
    return Inertia::render('Legal/SalesTerms');
})->name('sales.terms');

Route::get('/conditions-utilisation', function () {
    return Inertia::render('Legal/TermsOfService');
})->name('terms.show');

Route::get('/politique-confidentialite', function () {
    return Inertia::render('Legal/PrivacyPolicy');
})->name('policy.show');

Route::get('/politique-cookies', function () {
    return Inertia::render('Legal/CookiePolicy');
})->name('cookies.policy');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
])->group(function () {
    // Accessible même si l’e-mail n’est pas encore vérifié.
    Route::get('/parametres', [UserProfileController::class, 'show'])
        ->name('profile.show');

    Route::delete('/parametres/sessions', [OtherBrowserSessionsController::class, 'destroy'])
        ->name('other-browser-sessions.destroy');

    Route::delete('/parametres', [CurrentUserController::class, 'destroy'])
        ->name('current-user.destroy');
});

// =========================================================================
// 1. ROUTES PUBLIQUES (FRONT)
// =========================================================================

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/ateliers', [AtelierController::class, 'index'])->name('ateliers.index');
Route::get('/stages', [StageController::class, 'index'])->name('stages.index');
Route::get('/faq', FaqController::class)->name('faq.index');
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::get('/galerie', GalerieController::class)->name('gallery.index');

Route::get('/calendrier/prochaine-seance', [CalendrierController::class, 'nextLesson'])
    ->name('calendrier.next-lesson');

Route::get('/calendrier', CalendrierController::class)->name('calendrier.index');

// =========================================================================
// REDIRECTION APRÈS AUTHENTIFICATION
// =========================================================================

Route::get('/auth/continuer', function (Request $request) {
    $target = $request->query(
        'to',
        route('member.dashboard', absolute: false)
    );

    // Autorise uniquement une URL interne.
    if (
        ! is_string($target)
        || ! str_starts_with($target, '/')
        || str_starts_with($target, '//')
    ) {
        $target = route('member.dashboard', absolute: false);
    }

    return redirect($target);
})->middleware('auth')->name('auth.continue');

// =========================================================================
// 2. RÉSERVATIONS & ACTIONS PRECOGNITION (AUTH)
// =========================================================================

Route::middleware(['auth', HandlePrecognitiveRequests::class])->group(function () {
    Route::post('/reservations', EnrollmentController::class)->name('reservations.store');
    Route::post('/attendees', [AttendeeController::class, 'store'])->name('attendees.store');

    Route::get('/lessons/{lesson}/module-preview', ModulePreviewController::class)
        ->name('lessons.module-preview');
});

// =========================================================================
// 3. ESPACE MEMBRE
// =========================================================================

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/mon-compte', MemberDashboardController::class)->name('member.dashboard');

    Route::get('/mon-compte/modules/{module}', [MemberModuleController::class, 'show'])
        ->name('member.modules.show');

    Route::post('/mon-compte/inscriptions/{enrollment}/absence', [AbsenceController::class, 'declare'])
        ->name('member.absences.declare');

    Route::post('/mon-compte/absences/{absence}/annuler', [AbsenceController::class, 'cancel'])
        ->name('member.absences.cancel');

    Route::post('/mon-compte/invites', [AttendeeController::class, 'store'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('member.attendees.store');

    Route::put('/mon-compte/invites/{attendee}', [AttendeeController::class, 'update'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('member.attendees.update');

    Route::delete('/mon-compte/invites/{attendee}', [AttendeeController::class, 'destroy'])
        ->name('member.attendees.destroy');

    Route::patch('/mon-compte/inscriptions/{enrollment}/poste', [MemberEnrollmentController::class, 'updateSpotType'])
        ->name('member.enrollments.update-spot-type');
});

// =========================================================================
// 4. ROUTES DE TESTS TECHNIQUES
// =========================================================================

Route::middleware('web')->group(function () {
    Route::get('/test/blade', [AdminTestController::class, 'blade'])->name('test.blade');
    Route::post('/test/blade', [AdminTestController::class, 'bladePost'])->name('test.bladePost');

    Route::get('/validation-test', function () {
        $validator = Validator::make(
            ['test' => 'abcdefghijklmnopqrstuv', 'test2' => 'lol'],
            ['test' => 'string|max:30', 'test2' => 'boolean']
        );

        return [
            'fails' => $validator->fails(),
            'errors' => $validator->errors()->all(),
            'passes' => $validator->passes(),
        ];
    });
});

// =========================================================================
// 5. PANNEAU D’ADMINISTRATION
// =========================================================================

Route::middleware([
    'auth',
    'verified',
    CheckAdminRole::class,
    HandlePrecognitiveRequests::class,
])->prefix('admin')->group(function () {
    // Tests internes
    Route::get('/test', [AdminTestController::class, 'index'])->name('test.index');
    Route::get('/test/redux', [AdminTestController::class, 'redux'])->name('test.redux');

    // Tableau de bord principal
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard.index');

    // --- CONGÉS & FERMETURES ---
    Route::get('/conges-fermetures', [AdminStudioClosureController::class, 'index'])
        ->name('studio-closures.index');
    Route::post('/conges-fermetures', [AdminStudioClosureController::class, 'store'])
        ->name('studio-closures.store');
    Route::patch('/conges-fermetures/{studioClosure}', [AdminStudioClosureController::class, 'update'])
        ->name('studio-closures.update');
    Route::delete('/conges-fermetures/{studioClosure}', [AdminStudioClosureController::class, 'destroy'])
        ->name('studio-closures.destroy');

    // --- INSTRUCTEURS ---
    Route::get('/instructeurs', [AdminInstructorController::class, 'index'])->name('instructors.index');
    Route::post('/instructeurs', [AdminInstructorController::class, 'store'])->name('instructors.store');
    Route::patch('/instructeurs/{instructor}', [AdminInstructorController::class, 'update'])->name('instructors.update');
    Route::delete('/instructeurs/{instructor}', [AdminInstructorController::class, 'destroy'])->name('instructors.destroy');

    // --- COURS ---
    Route::get('/cours', [AdminCourseController::class, 'index'])->name('courses.index');
    Route::get('/cours/create', [AdminCourseController::class, 'create'])->name('courses.create');
    Route::post('/cours/preview', [AdminCourseController::class, 'preview'])->name('courses.preview');
    Route::get('/cours/{course}', [AdminCourseController::class, 'show'])->name('courses.show');
    Route::post('/cours', [AdminCourseController::class, 'store'])->name('courses.store');
    Route::patch('/cours/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
    Route::delete('/cours/{course}', [AdminCourseController::class, 'delete'])->name('courses.delete');

    // --- SÉANCES ---
    Route::get('/seances/{lesson}', [AdminLessonController::class, 'show'])->name('lessons.show');
    Route::get('/seances/{lesson}/attendees', [AdminLessonController::class, 'attendees'])->name('lessons.attendees');
    Route::patch('/seances/{lesson}', [AdminLessonController::class, 'update'])->name('lessons.update');
    Route::delete('/seances/{lesson}', [AdminLessonController::class, 'delete'])->name('lessons.delete');

    // --- RÉPERTOIRE DES MEMBRES & ACCOMPAGNANTS ---
    Route::get('/membres', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/membres/export/csv', [AdminUserController::class, 'exportCsv'])->name('users.export-csv');
    Route::get('/membres/search-recipients', [AdminUserController::class, 'searchRecipients'])->name('users.search-recipients');
    Route::post('/membres/email/preview', [AdminUserController::class, 'previewEmail'])->name('users.preview-email');
    Route::post('/membres/email/test', [AdminUserController::class, 'sendTestEmail'])->name('users.send-test-email');
    Route::post('/membres/check-recipients', [AdminUserController::class, 'checkRecipients'])->name('users.check-recipients');
    Route::post('/membres/email/bulk', [AdminUserController::class, 'sendBulkEmail'])->name('users.send-bulk-email');
    Route::delete('/membres/bulk-delete', [AdminUserController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::get('/membres/{user}', [AdminUserController::class, 'show'])->name('users.show');

    // --- FAQ ---
    Route::get('/faq', [AdminFaqController::class, 'index'])->name('faqs.index');
    Route::post('/faq', [AdminFaqController::class, 'store'])->name('faqs.store');
    Route::patch('/faq/{faq}', [AdminFaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faq/{faq}', [AdminFaqController::class, 'destroy'])->name('faqs.destroy');

    // --- GALERIE ---
    Route::get('/galerie', [AdminGalleryController::class, 'index'])
        ->name('gallery-images.index');
    Route::post('/galerie', [AdminGalleryController::class, 'store'])
        ->name('gallery-images.store');
    Route::patch('/galerie/ordre', [AdminGalleryController::class, 'reorder'])
        ->name('gallery-images.reorder');
    Route::patch('/galerie/{galleryImage}', [AdminGalleryController::class, 'update'])
        ->whereNumber('galleryImage')
        ->name('gallery-images.update');
    Route::delete('/galerie/{galleryImage}', [AdminGalleryController::class, 'destroy'])
        ->whereNumber('galleryImage')
        ->name('gallery-images.destroy');
});
