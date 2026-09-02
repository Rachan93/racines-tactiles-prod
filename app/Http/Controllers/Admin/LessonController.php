<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonUpdateRequest;
use App\Models\Attendee;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LessonController extends Controller
{
    /**
     * Endpoint JSON on-demand : charge les participants d'une séance uniquement au clic.
     */
    public function attendees(Lesson $lesson): JsonResponse
    {
        $enrollments = $lesson->enrollments()
            ->with([
                'module.participant' => function ($morph) {
                    if (method_exists($morph->getModel(), 'user')) {
                        $morph->with('user:id,first_name,last_name,email,phone_number');
                    }
                },
                'replacesAbsence',
            ])
            ->get();

        $attendees = $enrollments->map(function ($enrollment) {
            $participant = $enrollment->module?->participant;
            $isAttendee = $participant instanceof Attendee;
            $parentUser = $isAttendee ? $participant->user : null;

            return [
                'id' => $enrollment->id,
                'status' => $enrollment->status, // 'registered' | 'absent'
                'enrollment_type' => $enrollment->enrollment_type, // 'regular' | 'makeup'
                'spot_type' => $enrollment->spot_type, // 'wheel' | 'handbuilding'
                'is_substitute' => (bool) $enrollment->replaces_absence_id,
                'name' => $participant ? ($participant->first_name . ' ' . $participant->last_name) : 'Élève inconnu',
                'email' => $isAttendee ? ($parentUser?->email ?? '-') : ($participant?->email ?? '-'),
                'phone' => $isAttendee ? ($parentUser?->phone_number ?? '-') : ($participant?->phone_number ?? '-'),
                'is_attendee' => $isAttendee,
                // ID pour lien route('users.show')
                'user_id' => $isAttendee ? null : $participant?->id,
                'parent_user_id' => $parentUser?->id,
                'parent_user_name' => $parentUser ? ($parentUser->first_name . ' ' . $parentUser->last_name) : null,
            ];
        });

        return response()->json($attendees);
    }

    /**
     * Met à jour une séance (surcharges personnalisées ou annulation).
     */
    public function update(LessonUpdateRequest $request, Lesson $lesson): RedirectResponse
    {
        $validated = $request->validated();
        $isOverridden = (bool) $validated['is_overridden'];
        $isCancelled = (bool) $validated['is_cancelled'];

        if (! $isOverridden) {
            // Rétablissement du mode hérité du cours parent
            $lesson->update([
                'is_overridden' => false,
                'override_instructor_id' => null,
                'override_start_time' => null,
                'override_end_time' => null,
                'override_spots_max_wheel' => null,
                'override_spots_max_handbuilding' => null,
                'override_price' => null,
                'is_cancelled' => $isCancelled,
                'cancellation_reason' => $isCancelled ? ($validated['cancellation_reason'] ?? null) : null,
            ]);
        } else {
            // Application des surcharges personnalisées
            $lesson->update([
                'is_overridden' => true,
                'date' => $validated['date'] ?? $lesson->date,
                'override_instructor_id' => $validated['override_instructor_id'] ?? null,
                'override_start_time' => $validated['override_start_time'] ?? null,
                'override_end_time' => $validated['override_end_time'] ?? null,
                'override_spots_max_wheel' => $validated['override_spots_max_wheel'] ?? null,
                'override_spots_max_handbuilding' => $validated['override_spots_max_handbuilding'] ?? null,
                'override_price' => $validated['override_price'] ?? null,
                'is_cancelled' => $isCancelled,
                'cancellation_reason' => $isCancelled ? ($validated['cancellation_reason'] ?? null) : null,
            ]);
        }

        return redirect()->back();
    }

    /**
     * Supprime une séance si aucun élève n'y est inscrit.
     */
    public function delete(Lesson $lesson): RedirectResponse
    {
        $registeredCount = $lesson->enrollments()->where('status', 'registered')->count();

        // Verrou de sécurité strict
        if ($registeredCount > 0) {
            abort(422, "Impossible de supprimer cette séance : des élèves y sont inscrits.");
        }

        $lesson->delete();

        return redirect()->back();
    }
}
