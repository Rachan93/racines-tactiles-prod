<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModulePreviewController extends Controller
{
    /**
     * Calcule la date de fin réelle et la complétude d'un module de N séances.
     */
    public function __invoke(Request $request, Lesson $lesson): JsonResponse
    {
        $totalLessons = max(1, (int) $request->query('total_lessons', 10));

        // Récupération de la séquence chronologique des dates des cours non annulés
        $dates = Lesson::query()
            ->where('course_id', $lesson->course_id)
            ->where('date', '>=', $lesson->date)
            ->where('is_cancelled', false)
            ->orderBy('date', 'asc')
            ->take($totalLessons)
            ->pluck('date');

        $foundCount = $dates->count();
        $endDate = $dates->last();

        return response()->json([
            'start_date' => Carbon::parse($lesson->date)->toDateString(),
            'end_date' => $endDate ? Carbon::parse($endDate)->toDateString() : null,
            'total_requested' => $totalLessons,
            'total_found' => $foundCount,
            'is_complete' => $foundCount >= $totalLessons,
        ]);
    }
}
