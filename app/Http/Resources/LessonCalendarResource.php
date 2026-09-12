<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $maxTotalRoom = 10;
        $maxWheelCap = 8;
        $maxHandbuildingCap = 4;

        $typeName = strtolower($this->course->type->name ?? '');
        $isCollective = str_contains($typeName, 'collectif') || (int) $this->course->type_id === 1;
        $allowsMakeup = $isCollective;

        $regularHandbuilding = $this->regular_handbuilding_seats ?? 0;
        $regularWheel = $this->regular_wheel_seats ?? 0;
        $regularTotal = $this->regular_total_seats ?? ($regularHandbuilding + $regularWheel);

        $physicalHandbuilding = $this->physical_handbuilding_count ?? 0;
        $physicalWheel = $this->physical_wheel_count ?? 0;
        $physicalTotal = $physicalHandbuilding + $physicalWheel;

        $roomRemaining = max(0, $maxTotalRoom - $regularTotal);
        $spotsAvailableHandbuilding = max(0, min($maxHandbuildingCap - $regularHandbuilding, $roomRemaining));
        $spotsAvailableWheel = max(0, min($maxWheelCap - $regularWheel, $roomRemaining));

        // Les deux postes sont des choix de réservation, pas deux capacités de salle cumulables.
        $totalStandardAvailable = min(
            $roomRemaining,
            $spotsAvailableHandbuilding + $spotsAvailableWheel,
        );

        $capacityFillRate = $maxTotalRoom > 0
            ? round(1 - ($totalStandardAvailable / $maxTotalRoom), 2)
            : 1.0;

        $capacityStatus = match (true) {
            $totalStandardAvailable === 0 => 'full',
            $capacityFillRate >= 0.7 => 'limited',
            default => 'available',
        };

        $lessonDate = Carbon::parse($this->date)->startOfDay();
        $today = Carbon::today();
        $maxWindow = Carbon::today()->addDays(6);
        $isWithinJ6 = $lessonDate->gte($today) && $lessonDate->lte($maxWindow);
        $isWithinBookingWindow = $isCollective ? $isWithinJ6 : true;

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

        $userEnrollments = $this->relationLoaded('enrollments') ? $this->enrollments : collect();
        $isUserEnrolled = $userEnrollments->isNotEmpty();

        $userEnrollmentDetails = $userEnrollments->map(function ($enrollment) {
            $participant = $enrollment->module?->participant;
            $name = $participant
                ? "{$participant->first_name} {$participant->last_name}"
                : 'Participant';

            return [
                'id' => $enrollment->id,
                'participant_id' => $participant?->id,
                'participant_type' => $enrollment->module?->participant_type,
                'name' => $name,
                'spot_type' => $enrollment->spot_type,
                'status' => $enrollment->status,
                'is_absent' => $enrollment->status === 'absent',
            ];
        })->values()->all();

        $dateStr = $lessonDate->format('Y-m-d');
        $startIso = Carbon::parse("{$dateStr} {$this->effective_start_time}")->toIso8601String();
        $endIso = Carbon::parse("{$dateStr} {$this->effective_end_time}")->toIso8601String();

        $instructor = $this->effective_instructor;
        $instructorName = $instructor
            ? "{$instructor->first_name} {$instructor->last_name}"
            : 'Non assigné';

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->course->name,
            'start' => $startIso,
            'end' => $endIso,
            'instructor' => $instructorName,
            'type' => $this->course->type->name ?? 'Général',
            'type_id' => (int) $this->course->type_id,
            'price' => (float) $this->effective_price,
            'is_collective' => $isCollective,
            'allows_makeup' => $allowsMakeup,
            'is_within_booking_window' => $isWithinBookingWindow,
            'is_user_enrolled' => $isUserEnrolled,
            'user_enrollments' => $userEnrollmentDetails,

            'capacity_status' => $capacityStatus,
            'capacity_fill_rate' => $capacityFillRate,
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
