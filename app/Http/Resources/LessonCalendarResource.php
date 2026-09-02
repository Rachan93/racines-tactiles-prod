<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // 1. Plafonds maximaux absolus de la salle
        $maxTotalRoom = 10;
        $maxWheelCap = 8;
        $maxHandbuildingCap = 4;

        // 2. Détection du type de formule
        $typeName = strtolower($this->course->type->name ?? '');
        $isCollective = str_contains($typeName, 'collectif') || (int) $this->course->type_id === 1;
        $allowsMakeup = $isCollective;

        // 3. Effectifs réguliers (modules réservés)
        $regularHandbuilding = $this->regular_handbuilding_seats ?? 0;
        $regularWheel = $this->regular_wheel_seats ?? 0;
        $regularTotal = $this->regular_total_seats ?? ($regularHandbuilding + $regularWheel);

        // 4. Effectifs physiques réels (présents sur ce cours)
        $physicalHandbuilding = $this->physical_handbuilding_count ?? 0;
        $physicalWheel = $this->physical_wheel_count ?? 0;
        $physicalTotal = $physicalHandbuilding + $physicalWheel;

        // 5. Places STANDARD disponibles pour nouveaux modules
        $roomRemaining = max(0, $maxTotalRoom - $regularTotal);
        $spotsAvailableHandbuilding = max(0, min($maxHandbuildingCap - $regularHandbuilding, $roomRemaining));
        $spotsAvailableWheel = max(0, min($maxWheelCap - $regularWheel, $roomRemaining));
        $totalStandardAvailable = $spotsAvailableHandbuilding + $spotsAvailableWheel;

        // 6. Règle temporelle J+6 (stricte pour collectifs, libre pour stages/privés)
        $lessonDate = Carbon::parse($this->date)->startOfDay();
        $today = Carbon::today();
        $maxWindow = Carbon::today()->addDays(6);
        $isWithinJ6 = $lessonDate->gte($today) && $lessonDate->lte($maxWindow);

        $isWithinBookingWindow = $isCollective ? $isWithinJ6 : true;

        // 7. Calcul de l'existence de places de RATTRAPAGE
        if (! $allowsMakeup) {
            $makeupsAvailableHandbuilding = 0;
            $makeupsAvailableWheel = 0;
        } else {
            $absenceMakeupsHandbuilding = max(0, ($this->absent_handbuilding_count ?? 0) - ($this->makeup_handbuilding_count ?? 0));
            $absenceMakeupsWheel = max(0, ($this->absent_wheel_count ?? 0) - ($this->makeup_wheel_count ?? 0));

            if ($isWithinJ6) {
                $makeupsAvailableHandbuilding = $absenceMakeupsHandbuilding + $spotsAvailableHandbuilding;
                $makeupsAvailableWheel = $absenceMakeupsWheel + $spotsAvailableWheel;
            } else {
                $makeupsAvailableHandbuilding = $absenceMakeupsHandbuilding;
                $makeupsAvailableWheel = $absenceMakeupsWheel;
            }
        }

        $hasMakeupsAvailable = ($makeupsAvailableHandbuilding + $makeupsAvailableWheel) > 0;

        // 8. Inscriptions du compte connecté (Moi-même ou Invités)
        $userEnrollments = $this->relationLoaded('enrollments') ? $this->enrollments : collect();
        $isUserEnrolled = $userEnrollments->isNotEmpty();

        $userEnrollmentDetails = $userEnrollments->map(function ($enr) {
            $participant = $enr->module?->participant;
            $name = $participant ? "{$participant->first_name} {$participant->last_name}" : 'Participant';

            return [
                'id' => $enr->id,
                'participant_id' => $participant?->id,
                'participant_type' => $enr->module?->participant_type,
                'name' => $name,
                'spot_type' => $enr->spot_type,
                'status' => $enr->status,
                'is_absent' => $enr->status === 'absent',
            ];
        })->values()->all();

        // 9. Couleur FullCalendar (Rouge si 0 place standard, même si rattrapage dispo)
        $color = match (true) {
            $isUserEnrolled => '#3B82F6', // Bleu (Inscrit)
            $totalStandardAvailable === 0 => '#EF4444', // Rouge (Complet pour module régulier)
            $isCollective && ! $isWithinBookingWindow && ! $hasMakeupsAvailable => '#94A3B8', // Gris (Hors J+6 sans rattrapage)
            $totalStandardAvailable <= 3 => '#F59E0B', // Orange (1 à 3 places)
            default => '#10B981', // Vert (Disponible)
        };

        $dateStr = $lessonDate->format('Y-m-d');
        $startIso = Carbon::parse("{$dateStr} {$this->effective_start_time}")->toIso8601String();
        $endIso = Carbon::parse("{$dateStr} {$this->effective_end_time}")->toIso8601String();

        $instructor = $this->effective_instructor;
        $instructorName = $instructor ? "{$instructor->first_name} {$instructor->last_name}" : 'Non assigné';

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->course->name,
            'start' => $startIso,
            'end' => $endIso,
            'color' => $color,
            'instructor' => $instructorName,
            'type' => $this->course->type->name ?? 'Général',
            'type_id' => (int) $this->course->type_id,
            'price' => (float) $this->effective_price,
            'is_collective' => $isCollective,
            'allows_makeup' => $allowsMakeup,
            'is_within_booking_window' => $isWithinBookingWindow,
            'is_user_enrolled' => $isUserEnrolled,
            'user_enrollments' => $userEnrollmentDetails,

            // Capacités globales
            'total_room_max' => $maxTotalRoom,
            'total_physical_taken' => $physicalTotal,
            'total_regular_taken' => $regularTotal,
            'total_standard_available' => $totalStandardAvailable,
            'has_makeups_available' => $hasMakeupsAvailable,

            'handbuilding' => [
                'max' => $maxHandbuildingCap,
                'physical_taken' => $physicalHandbuilding,
                'regular_taken' => $regularHandbuilding,
                'standard_available' => $spotsAvailableHandbuilding,
                'has_makeup_available' => $makeupsAvailableHandbuilding > 0,
            ],

            'wheel' => [
                'max' => $maxWheelCap,
                'physical_taken' => $physicalWheel,
                'regular_taken' => $regularWheel,
                'standard_available' => $spotsAvailableWheel,
                'has_makeup_available' => $makeupsAvailableWheel > 0,
            ],
        ];
    }
}
