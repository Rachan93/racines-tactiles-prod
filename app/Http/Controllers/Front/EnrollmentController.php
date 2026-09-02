<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnrollmentStoreRequest;
use App\Models\Module;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Traite la soumission d'une réservation (Module complet ou Rattrapage).
     */
    public function __invoke(EnrollmentStoreRequest $request): RedirectResponse
    {
        // 1. Exécution de la réservation via le service métier (après validation réussie)
        $result = $this->bookingService->book($request->validated());

        // 2. Détermination du message de succès selon le type de réservation
        if ($result instanceof Module) {
            $message = "Votre module de {$result->total_lessons} séances a été réservé avec succès !";
        } else {
            $message = "Votre cours de rattrapage a été positionné avec succès !";
        }

        // 3. Redirection Inertia vers le calendrier avec notification Flash
        return redirect()->back()->with('success', $message);
    }
}
