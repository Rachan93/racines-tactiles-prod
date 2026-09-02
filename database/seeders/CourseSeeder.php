<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Course::factory(40)->create()->each(function ($course) {
            $startDate = Carbon::parse($course->first_lesson_date);
            $endDate = Carbon::parse($course->end_date);
            $currentDate = clone $startDate;

            // On garde une trace des leçons créées pour ce cours
            $courseLessons = collect();

            // Création des leçons
            while ($currentDate <= $endDate) {
                $lesson = Lesson::factory()->create([
                    'course_id' => $course->id,
                    'date' => $currentDate,
                ]);

                $courseLessons->push($lesson);
                $currentDate->addDays($course->frequency);
            }

            // Application des modifications sur un sous-ensemble des leçons
            $totalLessons = $courseLessons->count();

            // pourcentage des leçons qui auront des overrides aléatoires
            $overriddenCount = max(1, round($totalLessons * 0.20));
            $courseLessons->random($overriddenCount)
                ->each(function ($lesson) {
                    // Utilise la méthode randomOverrides pour des overrides partiels
                    $randomOverrideData = Lesson::factory()->randomOverrides()->raw();
                    $lesson->fill($randomOverrideData)->save();
                });

            // pourcentage des leçons qui seront annulées (indépendamment des overrides)
            $cancelledCount = max(1, round($totalLessons * 0.05));
            $courseLessons->random($cancelledCount)
                ->each(function ($lesson) {
                    $lesson->fill(
                        Lesson::factory()->cancelled()->raw()
                    )->save();
                });
        });
    }
}
