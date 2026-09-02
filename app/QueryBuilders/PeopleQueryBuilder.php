<?php

namespace App\QueryBuilders;

use App\Models\Attendee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PeopleQueryBuilder
{
    /**
     * Applique la recherche textuelle ciblée sur les colonnes demandées.
     */
    public function applySearch(Builder $query, ?string $search, array $filters, string $table): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $cleanSearch = trim($search);

        return $query->where(function (Builder $subQuery) use ($cleanSearch, $filters, $table) {
            foreach ($filters as $filter) {
                switch ($filter) {
                    case 'last_name':
                        $subQuery->orWhere("{$table}.last_name", 'like', "%{$cleanSearch}%");
                        break;
                    case 'first_name':
                        $subQuery->orWhere("{$table}.first_name", 'like', "%{$cleanSearch}%");
                        break;
                    case 'email':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.email", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'phone_number':
                        if ($table === 'users') {
                            $stripped = preg_replace('/\s+/', '', $cleanSearch);
                            $subQuery->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$table}.phone_number, ' ', ''), '-', ''), '.', ''), '(', ''), ')', '') LIKE ?", ["%{$stripped}%"]);
                        }
                        break;
                    case 'address':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.address", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'locality':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.locality", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'postal_code':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.postal_code", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'company_name':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.company_name", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'company_address':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.company_address", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'company_locality':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.company_locality", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'company_postal_code':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.company_postal_code", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'vat_number':
                        if ($table === 'users') {
                            $subQuery->orWhere("{$table}.vat_number", 'like', "%{$cleanSearch}%");
                        }
                        break;
                    case 'user_name':
                        if ($table === 'attendees') {
                            $subQuery->orWhereHas('user', function ($uq) use ($cleanSearch) {
                                $uq->where('users.last_name', 'like', "%{$cleanSearch}%")
                                   ->orWhere('users.first_name', 'like', "%{$cleanSearch}%");
                            });
                        }
                        break;
                    case 'user_email':
                        if ($table === 'attendees') {
                            $subQuery->orWhereHas('user', function ($uq) use ($cleanSearch) {
                                $uq->where('users.email', 'like', "%{$cleanSearch}%");
                            });
                        }
                        break;
                }
            }
        });
    }

    /**
     * Filtre les membres/invités inscrits à un cours spécifique via leurs séances.
     */
    public function applyCourseFilter(Builder $query, mixed $courseId): Builder
    {
        if (empty($courseId) || $courseId === 'all') {
            return $query;
        }

        return $query->whereHas('modules.enrollments.lesson', function (Builder $lq) use ($courseId) {
            $lq->where('lessons.course_id', $courseId);
        });
    }

    /**
     * Filtre selon le statut des modules (Actif, Terminé, Futur, Aucun).
     */
    public function applyModuleStatusFilter(Builder $query, ?string $status, string $table): Builder
    {
        if (empty($status) || $status === 'all') {
            return $query;
        }

        $today = now()->toDateString();

        switch ($status) {
            case 'none':
                return $query->whereDoesntHave('modules');

            case 'active':
                return $query->whereHas('modules.enrollments.lesson', function ($q) use ($today) {
                    $q->where('lessons.date', '>=', $today)->where('lessons.is_cancelled', false);
                })->whereHas('modules.enrollments.lesson', function ($q) use ($today) {
                    $q->where('lessons.date', '<=', $today);
                });

            case 'upcoming':
                return $query->whereHas('modules.enrollments.lesson', function ($q) use ($today) {
                    $q->where('lessons.date', '>', $today)->where('lessons.is_cancelled', false);
                })->whereDoesntHave('modules.enrollments.lesson', function ($q) use ($today) {
                    $q->where('lessons.date', '<=', $today);
                });

            case 'completed':
                return $query->whereHas('modules')
                    ->whereDoesntHave('modules.enrollments.lesson', function ($q) use ($today) {
                        $q->where('lessons.date', '>=', $today)->where('lessons.is_cancelled', false);
                    });

            default:
                return $query;
        }
    }

    /**
     * Filtre les membres/invités par date de séance avec opérateurs.
     */
    public function applyLessonDateFilter(Builder $query, ?string $operator, ?string $date, ?string $dateEnd, string $table): Builder
    {
        if (empty($operator) || empty($date)) {
            return $query;
        }

        return $query->whereHas('modules.enrollments', function (Builder $eq) use ($operator, $date, $dateEnd) {
            $eq->where('enrollments.status', 'registered')
               ->whereHas('lesson', function (Builder $lq) use ($operator, $date, $dateEnd) {
                   $lq->where('lessons.is_cancelled', false);

                   switch ($operator) {
                       case 'before':
                           $lq->whereDate('lessons.date', '<', $date);
                           break;
                       case 'after':
                           $lq->whereDate('lessons.date', '>', $date);
                           break;
                       case 'equal':
                           $lq->whereDate('lessons.date', '=', $date);
                           break;
                       case 'before_equal':
                           $lq->whereDate('lessons.date', '<=', $date);
                           break;
                       case 'after_equal':
                           $lq->whereDate('lessons.date', '>=', $date);
                           break;
                       case 'between':
                           if (! empty($dateEnd)) {
                               $lq->whereDate('lessons.date', '>=', $date)
                                  ->whereDate('lessons.date', '<=', $dateEnd);
                           }
                           break;
                   }
               });
        });
    }

    /**
     * Filtre par date de création / inscription.
     */
    public function applyCreatedAtFilter(Builder $query, ?string $operator, ?string $date, ?string $dateEnd, string $table): Builder
    {
        if (empty($operator) || empty($date)) {
            return $query;
        }

        switch ($operator) {
            case 'before':
                return $query->whereDate("{$table}.created_at", '<', $date);
            case 'after':
                return $query->whereDate("{$table}.created_at", '>', $date);
            case 'equal':
                return $query->whereDate("{$table}.created_at", '=', $date);
            case 'before_equal':
                return $query->whereDate("{$table}.created_at", '<=', $date);
            case 'after_equal':
                return $query->whereDate("{$table}.created_at", '>=', $date);
            case 'between':
                if (! empty($dateEnd)) {
                    return $query->whereDate("{$table}.created_at", '>=', $date)
                        ->whereDate("{$table}.created_at", '<=', $dateEnd);
                }
                return $query;
            default:
                return $query;
        }
    }

    /**
     * Filtre par date d'anniversaire (support 'Tous' sur jour/mois/année).
     */
    public function applyBirthdayFilter(Builder $query, array $birthdayFilters, string $table): Builder
    {
        $operator = $birthdayFilters['operator'] ?? null;
        $day = !empty($birthdayFilters['day']) && $birthdayFilters['day'] !== 'all' ? (int) $birthdayFilters['day'] : null;
        $month = !empty($birthdayFilters['month']) && $birthdayFilters['month'] !== 'all' ? (int) $birthdayFilters['month'] : null;
        $year = !empty($birthdayFilters['year']) && $birthdayFilters['year'] !== 'all' ? (int) $birthdayFilters['year'] : null;

        $endDay = !empty($birthdayFilters['endDay']) && $birthdayFilters['endDay'] !== 'all' ? (int) $birthdayFilters['endDay'] : null;
        $endMonth = !empty($birthdayFilters['endMonth']) && $birthdayFilters['endMonth'] !== 'all' ? (int) $birthdayFilters['endMonth'] : null;
        $endYear = !empty($birthdayFilters['endYear']) && $birthdayFilters['endYear'] !== 'all' ? (int) $birthdayFilters['endYear'] : null;

        if (empty($operator)) {
            return $query;
        }

        if ($day === null && $month === null && $year === null) {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($operator, $day, $month, $year, $endDay, $endMonth, $endYear, $table) {
            if ($day === null && $month !== null) {
                $this->applyMonthOnlyBirthdayFilter($subQuery, $operator, $month, $endMonth, $year, $table);
                return;
            }

            if ($day !== null && $month === null) {
                $this->applyDayOnlyBirthdayFilter($subQuery, $operator, $day, $endDay, $table);
                return;
            }

            $firstDateConditions = [
                ["DAY({$table}.birthday)", '=', $day],
                ["MONTH({$table}.birthday)", '=', $month],
            ];

            if ($year !== null) {
                $firstDateConditions[] = ["YEAR({$table}.birthday)", '=', $year];
            }

            switch ($operator) {
                case 'before':
                    $this->applyBirthdayComparison($subQuery, $firstDateConditions, '<', $table);
                    break;
                case 'after':
                    $this->applyBirthdayComparison($subQuery, $firstDateConditions, '>', $table);
                    break;
                case 'equal':
                    $this->applyBirthdayComparison($subQuery, $firstDateConditions, '=', $table);
                    break;
                case 'before_equal':
                    $this->applyBirthdayComparison($subQuery, $firstDateConditions, '<=', $table);
                    break;
                case 'after_equal':
                    $this->applyBirthdayComparison($subQuery, $firstDateConditions, '>=', $table);
                    break;
                case 'between':
                    if ($endDay !== null && $endMonth !== null) {
                        $secondDateConditions = [
                            ["DAY({$table}.birthday)", '=', $endDay],
                            ["MONTH({$table}.birthday)", '=', $endMonth],
                        ];

                        if ($endYear !== null) {
                            $secondDateConditions[] = ["YEAR({$table}.birthday)", '=', $endYear];
                        }

                        $this->applyBirthdayBetween($subQuery, $firstDateConditions, $secondDateConditions, $table);
                    }
                    break;
            }
        });
    }

    /**
     * Applique le tri sécurisé sur les colonnes.
     */
    public function applySorting(Builder $query, ?string $field, ?string $direction, string $table): Builder
    {
        $dir = strtolower($direction ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $field = $field ?? 'last_name';

        if ($table === 'users') {
            if (in_array($field, ['first_name', 'last_name', 'email', 'phone_number', 'locality', 'postal_code', 'company_name', 'birthday', 'created_at'])) {
                return $query->orderBy("{$table}.{$field}", $dir);
            } elseif ($field === 'modules_count') {
                return $query->withCount('modules')->orderBy('modules_count', $dir);
            } elseif ($field === 'attendees_count') {
                return $query->withCount('attendees')->orderBy('attendees_count', $dir);
            }
        } elseif ($table === 'attendees') {
            if (in_array($field, ['first_name', 'last_name', 'birthday', 'created_at'])) {
                return $query->orderBy("{$table}.{$field}", $dir);
            } elseif ($field === 'user_name') {
                return $query->join('users', 'attendees.user_id', '=', 'users.id')
                    ->orderBy('users.last_name', $dir)
                    ->select('attendees.*');
            } elseif ($field === 'modules_count') {
                return $query->withCount('modules')->orderBy('modules_count', $dir);
            }
        }

        return $query->orderBy("{$table}.last_name", $dir);
    }

    /**
     * Construit la requête globale des membres (users).
     */
    public function getUsersQuery(array $filters): Builder
    {
        $query = User::query()
            ->with(['attendees:id,user_id,first_name,last_name,birthday'])
            ->withCount(['modules', 'attendees']);

        $this->applySearch($query, $filters['users_search'] ?? '', $filters['users_search_filters'] ?? [], 'users');
        $this->applyCourseFilter($query, $filters['users_course_id'] ?? null);
        $this->applyModuleStatusFilter($query, $filters['users_module_status'] ?? 'all', 'users');
        $this->applyLessonDateFilter(
            $query,
            $filters['users_lesson_date_operator'] ?? '',
            $filters['users_lesson_date'] ?? '',
            $filters['users_lesson_date_end'] ?? '',
            'users'
        );
        $this->applyCreatedAtFilter($query, $filters['users_created_at_operator'] ?? '', $filters['users_created_at_date'] ?? '', $filters['users_created_at_date_end'] ?? '', 'users');
        $this->applyBirthdayFilter($query, [
            'operator' => $filters['users_birthday_operator'] ?? '',
            'day' => $filters['users_birthday_day'] ?? '',
            'month' => $filters['users_birthday_month'] ?? '',
            'year' => $filters['users_birthday_year'] ?? '',
            'endDay' => $filters['users_birthday_end_day'] ?? '',
            'endMonth' => $filters['users_birthday_end_month'] ?? '',
            'endYear' => $filters['users_birthday_end_year'] ?? '',
        ], 'users');

        $this->applySorting($query, $filters['users_sortField'] ?? 'last_name', $filters['users_sortDirection'] ?? 'asc', 'users');

        return $query;
    }

    /**
     * Construit la requête globale des invités (attendees).
     */
    public function getAttendeesQuery(array $filters): Builder
    {
        $query = Attendee::query()
            ->with(['user:id,first_name,last_name,email,phone_number'])
            ->withCount('modules');

        $this->applySearch($query, $filters['attendees_search'] ?? '', $filters['attendees_search_filters'] ?? [], 'attendees');
        $this->applyCourseFilter($query, $filters['attendees_course_id'] ?? null);
        $this->applyModuleStatusFilter($query, $filters['attendees_module_status'] ?? 'all', 'attendees');
        $this->applyLessonDateFilter(
            $query,
            $filters['attendees_lesson_date_operator'] ?? '',
            $filters['attendees_lesson_date'] ?? '',
            $filters['attendees_lesson_date_end'] ?? '',
            'attendees'
        );
        $this->applyCreatedAtFilter($query, $filters['attendees_created_at_operator'] ?? '', $filters['attendees_created_at_date'] ?? '', $filters['attendees_created_at_date_end'] ?? '', 'attendees');
        $this->applyBirthdayFilter($query, [
            'operator' => $filters['attendees_birthday_operator'] ?? '',
            'day' => $filters['attendees_birthday_day'] ?? '',
            'month' => $filters['attendees_birthday_month'] ?? '',
            'year' => $filters['attendees_birthday_year'] ?? '',
            'endDay' => $filters['attendees_birthday_end_day'] ?? '',
            'endMonth' => $filters['attendees_birthday_end_month'] ?? '',
            'endYear' => $filters['attendees_birthday_end_year'] ?? '',
        ], 'attendees');

        $this->applySorting($query, $filters['attendees_sortField'] ?? 'last_name', $filters['attendees_sortDirection'] ?? 'asc', 'attendees');

        return $query;
    }

    private function applyMonthOnlyBirthdayFilter(Builder $query, string $operator, int $month, ?int $endMonth, ?int $year, string $table): void
    {
        if ($year !== null) {
            $query->whereYear("{$table}.birthday", $year);
        }

        switch ($operator) {
            case 'equal':
                $query->whereMonth("{$table}.birthday", '=', $month);
                break;
            case 'before':
            case 'before_equal':
                $op = $operator === 'before' ? '<' : '<=';
                $query->whereMonth("{$table}.birthday", $op, $month);
                break;
            case 'after':
            case 'after_equal':
                $op = $operator === 'after' ? '>' : '>=';
                $query->whereMonth("{$table}.birthday", $op, $month);
                break;
            case 'between':
                if ($endMonth !== null) {
                    $query->whereMonth("{$table}.birthday", '>=', $month)
                          ->whereMonth("{$table}.birthday", '<=', $endMonth);
                }
                break;
        }
    }

    private function applyDayOnlyBirthdayFilter(Builder $query, string $operator, int $day, ?int $endDay, string $table): void
    {
        switch ($operator) {
            case 'equal':
                $query->whereDay("{$table}.birthday", '=', $day);
                break;
            case 'before':
            case 'before_equal':
                $op = $operator === 'before' ? '<' : '<=';
                $query->whereDay("{$table}.birthday", $op, $day);
                break;
            case 'after':
            case 'after_equal':
                $op = $operator === 'after' ? '>' : '>=';
                $query->whereDay("{$table}.birthday", $op, $day);
                break;
            case 'between':
                if ($endDay !== null) {
                    $query->whereDay("{$table}.birthday", '>=', $day)
                          ->whereDay("{$table}.birthday", '<=', $endDay);
                }
                break;
        }
    }

    private function applyBirthdayComparison(Builder $query, array $conditions, string $operator, string $table): void
    {
        $values = $this->extractConditionValues($conditions);
        $day = $values["DAY({$table}.birthday)"];
        $month = $values["MONTH({$table}.birthday)"];
        $year = $values["YEAR({$table}.birthday)"] ?? null;

        if ($year === null) {
            $query->whereRaw("CONCAT(LPAD(MONTH({$table}.birthday), 2, '0'), LPAD(DAY({$table}.birthday), 2, '0')) {$operator} CONCAT(LPAD(?, 2, '0'), LPAD(?, 2, '0'))", [$month, $day]);
        } else {
            $formattedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $query->whereRaw("DATE(CONCAT(YEAR({$table}.birthday), '-', MONTH({$table}.birthday), '-', DAY({$table}.birthday))) {$operator} ?", [$formattedDate]);
        }
    }

    private function applyBirthdayBetween(Builder $query, array $firstConditions, array $secondConditions, string $table): void
    {
        $firstValues = $this->extractConditionValues($firstConditions);
        $secondValues = $this->extractConditionValues($secondConditions);

        $day1 = $firstValues["DAY({$table}.birthday)"];
        $month1 = $firstValues["MONTH({$table}.birthday)"];
        $year1 = $firstValues["YEAR({$table}.birthday)"] ?? null;

        $day2 = $secondValues["DAY({$table}.birthday)"];
        $month2 = $secondValues["MONTH({$table}.birthday)"];
        $year2 = $secondValues["YEAR({$table}.birthday)"] ?? null;

        if ($year1 === null && $year2 === null) {
            $mmdd1 = sprintf('%02d%02d', $month1, $day1);
            $mmdd2 = sprintf('%02d%02d', $month2, $day2);

            if ($mmdd1 <= $mmdd2) {
                $query->whereRaw("CONCAT(LPAD(MONTH({$table}.birthday), 2, '0'), LPAD(DAY({$table}.birthday), 2, '0')) BETWEEN ? AND ?", [$mmdd1, $mmdd2]);
            } else {
                $query->whereRaw("(CONCAT(LPAD(MONTH({$table}.birthday), 2, '0'), LPAD(DAY({$table}.birthday), 2, '0')) >= ? OR CONCAT(LPAD(MONTH({$table}.birthday), 2, '0'), LPAD(DAY({$table}.birthday), 2, '0')) <= ?)", [$mmdd1, $mmdd2]);
            }
        } else {
            $year1 = $year1 ?? 1900;
            $year2 = $year2 ?? 2100;

            $date1 = sprintf('%04d-%02d-%02d', $year1, $month1, $day1);
            $date2 = sprintf('%04d-%02d-%02d', $year2, $month2, $day2);

            $query->whereRaw("DATE(CONCAT(YEAR({$table}.birthday), '-', MONTH({$table}.birthday), '-', DAY({$table}.birthday))) BETWEEN ? AND ?", [$date1, $date2]);
        }
    }

    private function extractConditionValues(array $conditions): array
    {
        $values = [];
        foreach ($conditions as $condition) {
            $values[$condition[0]] = $condition[2];
        }

        return $values;
    }
}
