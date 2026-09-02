<?php

namespace App\Actions\Users;

use App\QueryBuilders\PeopleQueryBuilder;

class GetUsersAction
{
    protected $queryBuilder;

    public function __construct(PeopleQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Custom function: Execute the action to retrieve filtered and paginated users.
     *
     * @param array $filters
     * @return array
     */
    public function execute(array $filters): array
    {
        $query = $this->queryBuilder->getUsersQuery($filters);

        // Paginate the results
        $pagination = $query->paginate($filters['users_perPage'], ['*'], 'users_page', $filters['users_page']);

        // Prepare pagination information
        $paginationInfo = [
            'page' => $pagination->currentPage(),
            'perPage' => (int) $filters['users_perPage'],
            'total' => $pagination->total(),
            'lastPage' => $pagination->lastPage(),
        ];

        // Prepare sorting information
        $sortingInfo = [
            'field' => $filters['users_sortField'],
            'direction' => $filters['users_sortDirection'],
        ];

        // Prepare filter information
        $appliedFilters = [
            'search' => $filters['users_search'],
            'registrationDate' => [
                'operator' => $filters['users_created_at_operator'],
                'date' => $filters['users_created_at_date'],
                'dateEnd' => $filters['users_created_at_date_end'],
            ],
            'birthday' => [
                'operator' => $filters['users_birthday_operator'],
                'day' => $filters['users_birthday_day'],
                'month' => $filters['users_birthday_month'],
                'year' => $filters['users_birthday_year'],
                'endDay' => $filters['users_birthday_end_day'],
                'endMonth' => $filters['users_birthday_end_month'],
                'endYear' => $filters['users_birthday_end_year'],
            ],
        ];

        return [
            'items' => $pagination->items(),
            'pagination' => $paginationInfo,
            'sorting' => $sortingInfo,
            'filters' => $appliedFilters,
        ];
    }
}
