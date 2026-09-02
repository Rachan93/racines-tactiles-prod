<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Absence;
use App\Models\Attendee;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class ModuleSeeder extends Seeder
{
    /**
     * Probabilité (%) qu'une absence donne lieu à un rattrapage simulé.
     */
    private const MAKEUP_CHANCE = 30;

    public function run(): void
    {
        User::all()->each(function (User $user) {
            $count = rand(0, 2);
            for ($i = 0; $i < $count; $i++) {
                $this->createModuleForUser($user);
            }
        });

        Attendee::all()->each(function (Attendee $attendee) {
            if (fake()->boolean(40)) {
                $this->createModuleForAttendee($attendee);
            }
        });
    }

    private function createModuleForUser(User $user): void
    {
        // On utilise make() pour préparer l'objet sans le sauver
        // car on doit déterminer le type et le total selon la 1ère leçon trouvée
        $proto = Module::factory()->forUser($user)->make();
        $this->seedEnrollmentsForModule($proto);
    }

    private function createModuleForAttendee(Attendee $attendee): void
    {
        $proto = Module::factory()->forAttendee($attendee)->make();
        $this->seedEnrollmentsForModule($proto);
    }

    private function seedEnrollmentsForModule(Module $proto): void
    {
        // 1. Récupération des leçons candidates
        $lessons = Lesson::query()
            ->with('course.type')
            ->where('is_cancelled', false)
            ->whereDate('date', '>=', $proto->purchase_date)
            ->when($proto->expiration_date, fn($q) => $q->whereDate('date', '<=', $proto->expiration_date))
            ->inRandomOrder()
            ->get();

        if ($lessons->isEmpty()) {
            return;
        }

        // 2. Chargement des places déjà occupées (Optimisation N+1)
        $lessonIds = $lessons->pluck('id')->toArray();

        $counts = Enrollment::query()
            ->select('lesson_id', 'spot_type', DB::raw('COUNT(*) as cnt'))
            ->whereIn('lesson_id', $lessonIds)
            ->whereIn('status', ['registered', 'absent'])
            ->groupBy('lesson_id', 'spot_type')
            ->get()
            ->reduce(function (array $carry, $row) {
                $carry[$row->lesson_id][$row->spot_type] = (int) $row->cnt;
                return $carry;
            }, []);

        // 3. Recherche de la première leçon disponible ("La clé de voûte")
        $firstLesson = null;
        $firstSpotType = null;

        foreach ($lessons as $lesson) {
            $spotType = $this->resolveAvailableSpot($lesson, $counts);
            if ($spotType) {
                $firstLesson = $lesson;
                $firstSpotType = $spotType;
                break;
            }
        }

        if (! $firstLesson) {
            return; // Impossible de caser le début du module
        }

        // 4. Définition et persistance du Module
        $type = $firstLesson->course->type;
        $totalLessons = match ($type->name) {
            'privé' => 1,
            'stage' => fake()->randomElement([3, 5]),
            default => 10, // collectif
        };

        $module = new Module();
        $module->fill($proto->getAttributes());
        $module->type_id = $type->id;
        $module->total_lessons = $totalLessons;
        $module->paid_price = $this->resolvePriceForType($type->name, $totalLessons);
        $module->save();

        // 5. Inscription à la première leçon
        $usedLessonIds = [$firstLesson->id];
        $pendingAbsences = [];

        $absence = $this->persistEnrollment($module, $firstLesson, $firstSpotType, $counts);
        if ($absence) {
            $pendingAbsences[] = ['absence' => $absence, 'lesson' => $firstLesson];
        }
        $createdCount = 1;

        // 6. Complétion du module avec d'autres leçons
        foreach ($lessons as $lesson) {
            // Conditions d'arrêt et de filtrage
            if ($createdCount >= $module->total_lessons) break;
            if ($lesson->id === $firstLesson->id) continue;
            if ($lesson->course->type_id !== $module->type_id) continue;

            // Vérification disponibilité
            $spotType = $this->resolveAvailableSpot($lesson, $counts);
            if (! $spotType) continue;

            $usedLessonIds[] = $lesson->id;

            // Inscription
            $absence = $this->persistEnrollment($module, $lesson, $spotType, $counts);
            if ($absence) {
                $pendingAbsences[] = ['absence' => $absence, 'lesson' => $lesson];
            }
            $createdCount++;
        }

        // 7. Ajustement final si le quota n'est pas atteint
        if ($createdCount < $module->total_lessons) {
            $module->update(['total_lessons' => $createdCount]);
        }

        // 8. (Bonus) Simulation occasionnelle de rattrapages pour les absences du module.
        // On réutilise $lessons et $counts déjà en mémoire : zéro requête SQL de plus.
        foreach ($pendingAbsences as $pending) {
            if (! fake()->boolean(self::MAKEUP_CHANCE)) {
                continue;
            }

            $this->attemptMakeup(
                $module,
                $pending['absence'],
                $pending['lesson'],
                $lessons,
                $usedLessonIds,
                $counts
            );
        }
    }

    /**
     * Vérifie les capacités et retourne le type de place dispo (wheel/handbuilding) ou null.
     */
    private function resolveAvailableSpot(Lesson $lesson, array $currentCounts): ?string
    {
        $maxWheel = $lesson->spots_max_wheel;
        $maxHB = $lesson->spots_max_handbuilding;

        $takenWheel = $currentCounts[$lesson->id]['wheel'] ?? 0;
        $takenHB = $currentCounts[$lesson->id]['handbuilding'] ?? 0;

        $wheelAvailable = $takenWheel < $maxWheel;
        $hbAvailable = $takenHB < $maxHB;

        if ($wheelAvailable && $hbAvailable) {
            return fake()->boolean(50) ? 'wheel' : 'handbuilding';
        }

        return match (true) {
            $wheelAvailable => 'wheel',
            $hbAvailable => 'handbuilding',
            default => null,
        };
    }

    /**
     * Crée l'enrollment en base, met à jour le compteur local (par référence),
     * et crée l'Absence associée si le statut tiré est 'absent'.
     *
     * @return Absence|null L'absence créée le cas échéant (réutilisée pour les rattrapages).
     */
    private function persistEnrollment(Module $module, Lesson $lesson, string $spotType, array &$counts): ?Absence
    {
        $attributes = $this->generateEnrollmentAttributes($lesson, $module, $spotType);

        $enrollment = Enrollment::create($attributes);

        // Si le statut consomme une place, on incrémente le compteur local pour les itérations suivantes
        if (in_array($attributes['status'], ['registered', 'absent'], true)) {
            if (!isset($counts[$lesson->id])) {
                $counts[$lesson->id] = [];
            }
            $counts[$lesson->id][$spotType] = ($counts[$lesson->id][$spotType] ?? 0) + 1;
        }

        if ($enrollment->status !== 'absent') {
            return null;
        }

        return $this->createAbsence($enrollment, $lesson);
    }

    /**
     * Calcule les attributs (statut, dates) selon la logique métier aléatoire.
     */
    private function generateEnrollmentAttributes(Lesson $lesson, Module $module, string $spotType): array
    {
        $lessonDate = Carbon::parse($lesson->date);
        $status = $this->pickRandomStatus($lessonDate);

        $cancellationDate = null;
        // Logique : on ne peut annuler à l'avance que si la leçon est dans le futur
        if ($status === 'cancelled' && $lessonDate->isFuture()) {
            $cancellationDate = (clone $lessonDate)->subDays(fake()->numberBetween(1, 7));
        }

        return [
            'lesson_id' => $lesson->id,
            'module_id' => $module->id,
            'status' => $status,
            'enrollment_type' => 'regular',
            'spot_type' => $spotType,
            'cancellation_date' => $cancellationDate,
        ];
    }

    /**
     * Logique de probabilité pour le statut.
     */
    private function pickRandomStatus(Carbon $lessonDate): string
    {
        $roll = fake()->numberBetween(1, 100);

        if ($lessonDate->isFuture()) {
            // Futur : 80% Présent, 15% Absent, 5% Annulé
            if ($roll <= 80) return 'registered';
            if ($roll <= 95) return 'absent';
            return 'cancelled';
        }

        // Passé : 85% Présent, 15% Absent (pas d'annulation rétroactive ici)
        return $roll <= 85 ? 'registered' : 'absent';
    }

    /**
     * Crée l'Absence liée à un enrollment marqué 'absent'.
     *
     * NB IMPORTANT : le modèle Absence n'expose actuellement que
     * enrollment_id, active, notification_date, cancellation_date dans son
     * $fillable. Il n'y a pas de champ `reason` — si tu veux en stocker un,
     * il faudra l'ajouter à la migration et au $fillable du modèle. En
     * attendant, on ne renseigne que les champs réellement disponibles.
     */
    private function createAbsence(Enrollment $enrollment, Lesson $lesson): Absence
    {
        $lessonDate = Carbon::parse($lesson->date);

        // Leçon future : l'élève prévient en amont de son absence.
        // Leçon passée : absence constatée le jour même (no-show non prévenu).
        $notificationDate = $lessonDate->isFuture()
            ? (clone $lessonDate)->subDays(fake()->numberBetween(1, 5))
            : clone $lessonDate;

        return Absence::create([
            'enrollment_id' => $enrollment->id,
            'active' => true,
            'notification_date' => $notificationDate,
            'cancellation_date' => null,
        ]);
    }

    /**
     * (Bonus) Tente de placer un rattrapage pour une absence donnée, en
     * réutilisant les leçons déjà chargées pour ce module — aucune requête
     * SQL supplémentaire, on reste sur le tableau $counts en mémoire.
     */
    private function attemptMakeup(
        Module $module,
        Absence $absence,
        Lesson $originalLesson,
        Collection $candidateLessons,
        array &$usedLessonIds,
        array &$counts
    ): void {
        $originalDate = Carbon::parse($originalLesson->date);

        $makeupLesson = null;
        $makeupSpotType = null;

        foreach ($candidateLessons as $lesson) {
            if (in_array($lesson->id, $usedLessonIds, true)) continue;
            if ($lesson->course->type_id !== $module->type_id) continue;
            if (! Carbon::parse($lesson->date)->greaterThan($originalDate)) continue;

            $spotType = $this->resolveAvailableSpot($lesson, $counts);
            if (! $spotType) continue;

            $makeupLesson = $lesson;
            $makeupSpotType = $spotType;
            break;
        }

        if (! $makeupLesson) {
            return; // Pas de créneau de rattrapage dispo, tant pis pour cette absence
        }

        Enrollment::create([
            'lesson_id' => $makeupLesson->id,
            'module_id' => $module->id,
            'status' => 'registered',
            'enrollment_type' => 'makeup',
            'spot_type' => $makeupSpotType,
            'replaces_absence_id' => $absence->id,
            'cancellation_date' => null,
        ]);

        $usedLessonIds[] = $makeupLesson->id;
        if (!isset($counts[$makeupLesson->id])) {
            $counts[$makeupLesson->id] = [];
        }
        $counts[$makeupLesson->id][$makeupSpotType] = ($counts[$makeupLesson->id][$makeupSpotType] ?? 0) + 1;

        // Le rattrapage referme le "crédit" d'absence : on le désactive.
        $absence->update([
            'active' => false,
            'cancellation_date' => Carbon::parse($makeupLesson->date)->subDays(fake()->numberBetween(0, 2)),
        ]);
    }

    /**
     * Détermine le prix payé selon le type de module et le nombre de séances.
     *
     * Grille indicative — à remplacer par votre tarification réelle.
     */
    private function resolvePriceForType(string $typeName, int $totalLessons): float
    {
        return match ($typeName) {
            'privé' => 45.0,
            'stage' => $totalLessons * 25.0,
            'collectif' => 180.0,
            default => 150.0,
        };
    }
}
