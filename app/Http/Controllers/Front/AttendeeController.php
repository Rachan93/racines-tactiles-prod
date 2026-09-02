<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendeeStoreRequest;
use App\Http\Requests\AttendeeUpdateRequest;
use App\Models\Attendee;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendeeController extends Controller
{
    /**
     * Enregistre un nouvel invité/enfant rattaché au compte de l'utilisateur.
     * (Conservé à l'identique pour la modale de réservation du calendrier)
     */
    public function store(AttendeeStoreRequest $request): RedirectResponse
    {
        $attendee = $request->user()->attendees()->create(
            $request->validated()
        );

        return redirect()->back()->with([
            'success' => "{$attendee->first_name} a été ajouté(e) avec succès !",
            'created_attendee' => $attendee,
        ]);
    }

    /**
     * Met à jour les informations d'un invité/enfant.
     */
    public function update(AttendeeUpdateRequest $request, Attendee $attendee): RedirectResponse
    {
        $attendee->update($request->validated());

        return redirect()->back()->with([
            'success' => "Les informations de {$attendee->first_name} ont été mises à jour.",
        ]);
    }

    /**
     * Supprime un invité/enfant sous réserve de sécurité d'intégrité des données.
     */
    public function destroy(Request $request, Attendee $attendee): RedirectResponse
    {
        // 1. Contrôle d'accès strict
        if ($attendee->user_id !== $request->user()->id) {
            abort(403, 'Action non autorisée.');
        }

        // 2. Vérification s'il existe des séances futures réservées
        $hasUpcomingEnrollments = Enrollment::query()
            ->whereHas('module', function ($q) use ($attendee) {
                $q->where('participant_type', Attendee::class)
                    ->where('participant_id', $attendee->id);
            })
            ->where('status', 'registered')
            ->whereHas('lesson', function ($q) {
                $q->where('date', '>=', now()->toDateString());
            })
            ->exists();

        if ($hasUpcomingEnrollments) {
            return redirect()->back()->withErrors([
                'attendee' => "Impossible de supprimer {$attendee->first_name} {$attendee->last_name} : des séances à venir sont encore réservées pour cet invité.",
            ]);
        }

        // 3. Vérification de l'historique de modules (intégrité référentielle)
        if ($attendee->modules()->exists()) {
            return redirect()->back()->withErrors([
                'attendee' => "Impossible de supprimer {$attendee->first_name} {$attendee->last_name} : un historique de cours ou d'achats est associé à cet invité.",
            ]);
        }

        $attendee->delete();

        return redirect()->back()->with([
            'success' => "{$attendee->first_name} {$attendee->last_name} a été supprimé(e) avec succès.",
        ]);
    }
}
