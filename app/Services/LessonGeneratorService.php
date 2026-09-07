<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\StudioClosure;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Holidays\Holidays;
use Throwable;

class LessonGeneratorService
{
    /**
     * Calcule et prévisualise la séquence des dates de cours.
     *
     * @param array $params [
     *     'first_lesson_date' => '2026-09-08',
     *     'end_date' => '2027-06-30',
     *     'frequency' => 7,
     *     'type_id' => 1,
     *     'exclude_public_holidays' => true,
     *     'exclude_school_holidays' => true,
     *     'exclude_studio_closures' => true,
     *     'exclude_weekends' => false,
     *     'country_code' => 'BE',
     * ]
     */
    public function previewSchedule(array $params): array
    {
        $firstDate = Carbon::parse($params['first_lesson_date'])
            ->startOfDay();

        $endDate = Carbon::parse($params['end_date'])
            ->startOfDay();

        $frequency = max(1, (int) ($params['frequency'] ?? 7));
        $typeId = $params['type_id'] ?? null;
        $countryCode = $params['country_code'] ?? 'BE';

        $excludePublicHolidays = filter_var(
            $params['exclude_public_holidays'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        );

        $excludeSchoolHolidays = filter_var(
            $params['exclude_school_holidays'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        );

        $excludeStudioClosures = filter_var(
            $params['exclude_studio_closures'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        );

        $excludeWeekends = filter_var(
            $params['exclude_weekends'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if ($firstDate->gt($endDate)) {
            return [
                'generated_dates' => [],
                'skipped_dates' => [],
                'total_generated' => 0,
                'total_skipped' => 0,
                'error' => 'La date de début ne peut pas être postérieure à la date de fin.',
            ];
        }

        // 1. Récupération des fermetures en une seule requête.
        $closures = StudioClosure::overlapping($firstDate, $endDate)
            ->where(function ($query) use ($typeId) {
                $query
                    ->whereNull('type_id')
                    ->orWhere('type_id', $typeId);
            })
            ->get();

        // 2. Initialisation des jours fériés pour le pays.
        $holidays = null;

        if ($excludePublicHolidays) {
            try {
                $holidays = Holidays::for($countryCode);
            } catch (\Exception $exception) {
                $holidays = null;
            }
        }

        $generatedDates = [];
        $skippedDates = [];
        $currentDate = $firstDate->copy();

        // 3. Génération des dates selon la fréquence demandée.
        while ($currentDate->lte($endDate)) {
            $skipReason = null;
            $skipType = null;

            // A. Week-ends.
            if ($excludeWeekends && $currentDate->isWeekend()) {
                $skipReason = 'Week-end exclu';
                $skipType = 'weekend';
            }

            // B. Jours fériés légaux.
            if (! $skipReason && $excludePublicHolidays && $holidays) {
                if ($holidays->isHoliday($currentDate)) {
                    $holidayName = $holidays->getName($currentDate)
                        ?? 'Jour férié';

                    $skipReason = "Jour férié ({$holidayName})";
                    $skipType = 'public_holiday';
                }
            }

            // C. Vacances scolaires et fermetures de l'atelier.
            if (! $skipReason) {
                foreach ($closures as $closure) {
                    if (! $closure->coversDate($currentDate)) {
                        continue;
                    }

                    if (
                        $closure->type === 'school_holiday'
                        && $excludeSchoolHolidays
                    ) {
                        $skipReason = $closure->name;
                        $skipType = 'school_holiday';

                        break;
                    }

                    if (
                        $closure->type === 'studio_closure'
                        && $excludeStudioClosures
                    ) {
                        $skipReason = $closure->name;
                        $skipType = 'studio_closure';

                        break;
                    }
                }
            }

            // D. Attribution du résultat pour cette date.
            $dateFormatted = ucfirst(
                $currentDate->translatedFormat('D d M Y')
            );

            $dateIso = $currentDate->toDateString();

            $dayName = ucfirst(
                $currentDate->translatedFormat('l')
            );

            if ($skipReason) {
                $skippedDates[] = [
                    'date' => $dateIso,
                    'date_formatted' => $dateFormatted,
                    'day_name' => $dayName,
                    'reason' => $skipReason,
                    'type' => $skipType,
                ];
            } else {
                $generatedDates[] = [
                    'date' => $dateIso,
                    'date_formatted' => $dateFormatted,
                    'day_name' => $dayName,
                    'is_selected' => true,
                ];
            }

            $currentDate->addDays($frequency);
        }

        return [
            'generated_dates' => $generatedDates,
            'skipped_dates' => $skippedDates,
            'total_generated' => count($generatedDates),
            'total_skipped' => count($skippedDates),
            'first_date' => $firstDate->toDateString(),
            'last_date' => count($generatedDates) > 0
                ? end($generatedDates)['date']
                : null,
        ];
    }

    /**
     * Enregistre la couverture et crée le cours avec ses séances.
     *
     * Les données doivent provenir de CourseStoreRequest::validated().
     * Cette validation réserve l'image de couverture aux stages.
     *
     * Le cours et ses séances sont créés dans une transaction.
     * Si la création échoue, la couverture enregistrée est supprimée.
     *
     * @param array $courseData Données validées, avec éventuellement
     *                          un UploadedFile dans cover_image.
     * @param array $confirmedDates Dates au format ['2026-09-08', ...].
     */
    public function createCourseWithLessons(
        array $courseData,
        array $confirmedDates
    ): Course {
        $coverImage = $courseData['cover_image'] ?? null;
        $coverImagePath = null;

        // Le service attend un fichier validé, jamais un chemin fourni
        // directement par le formulaire.
        if ($coverImage !== null && ! ($coverImage instanceof UploadedFile)) {
            throw new RuntimeException(
                'L’image de couverture doit être un fichier téléversé valide.'
            );
        }

        if ($coverImage instanceof UploadedFile) {
            $storedPath = $coverImage->store(
                'courses/covers',
                'public'
            );

            if (! is_string($storedPath) || $storedPath === '') {
                throw new RuntimeException(
                    'Impossible d’enregistrer l’image de couverture.'
                );
            }

            $coverImagePath = $storedPath;
        }

        try {
            return DB::transaction(function () use (
                $courseData,
                $confirmedDates,
                $coverImagePath
            ) {
                // 1. Création du cours.
                $course = Course::create([
                    'name' => $courseData['name'],
                    'name_en' => $courseData['name_en'] ?? null,
                    'type_id' => $courseData['type_id'],
                    'default_instructor_id' => $courseData['default_instructor_id'],

                    'sub_type' => $courseData['sub_type'] ?? null,
                    'subtitle' => $courseData['subtitle'] ?? null,
                    'subtitle_en' => $courseData['subtitle_en'] ?? null,
                    'description' => $courseData['description'] ?? null,
                    'description_en' => $courseData['description_en'] ?? null,
                    'practical_info' => $courseData['practical_info'] ?? null,
                    'practical_info_en' => $courseData['practical_info_en'] ?? null,

                    // Chemin relatif au disque public.
                    'cover_image' => $coverImagePath,

                    'first_lesson_date' => $courseData['first_lesson_date'],
                    'end_date' => $courseData['end_date'],
                    'default_start_time' => $courseData['default_start_time'],
                    'default_end_time' => $courseData['default_end_time'],
                    'frequency' => $courseData['frequency'],

                    'default_spots_max_handbuilding' => $courseData['default_spots_max_handbuilding'],
                    'default_spots_max_wheel' => $courseData['default_spots_max_wheel'],
                    'default_price' => $courseData['default_price'],

                    'is_active' => filter_var(
                        $courseData['is_active'] ?? true,
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    'is_featured' => filter_var(
                        $courseData['is_featured'] ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    ),
                ]);

                // 2. Création des séances confirmées.
                foreach ($confirmedDates as $dateString) {
                    Lesson::create([
                        'course_id' => $course->id,
                        'date' => Carbon::parse($dateString)->toDateString(),
                        'is_cancelled' => false,
                        'is_overridden' => false,
                    ]);
                }

                return $course;
            });
        } catch (Throwable $exception) {
            // Les fichiers ne sont pas annulés par la transaction SQL.
            if ($coverImagePath !== null) {
                try {
                    $deleted = Storage::disk('public')
                        ->delete($coverImagePath);

                    if (! $deleted) {
                        report(new RuntimeException(
                            "Impossible de supprimer la couverture après échec : {$coverImagePath}"
                        ));
                    }
                } catch (Throwable $cleanupException) {
                    // Conserve l'erreur de création comme erreur principale.
                    report($cleanupException);
                }
            }

            throw $exception;
        }
    }
}
