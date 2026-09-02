<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Enrollment;
use App\Services\AbsenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    /**
     * Déclare une absence sur une séance future.
     */
    public function declare(Request $request, Enrollment $enrollment, AbsenceService $absenceService): RedirectResponse
    {
        $absence = $absenceService->declareAbsence($enrollment, $request->user());

        $message = ($absence && $absence->active)
            ? 'Votre absence a été enregistrée. Un crédit de rattrapage a été ajouté à votre compte.'
            : 'Votre absence a été enregistrée.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Annule une absence pour récupérer sa place.
     */
    public function cancel(Request $request, Absence $absence, AbsenceService $absenceService): RedirectResponse
    {
        $absenceService->cancelAbsence($absence, $request->user());

        return redirect()->back()->with('success', 'Votre absence a été annulée. Votre place a été rétablie avec succès !');
    }
}
