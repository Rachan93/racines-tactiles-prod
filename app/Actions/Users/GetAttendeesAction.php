<?php

namespace App\Actions\Users;

use App\QueryBuilders\PeopleQueryBuilder;

class GetAttendeesAction
{
    protected $queryBuilder;

    public function __construct(PeopleQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Custom function: Execute the action to retrieve filtered and paginated attendees.
     *
     * @param array $filters
     * @return array
     */
    public function execute(array $filters): array
    {
        $query = $this->queryBuilder->getAttendeesQuery($filters);

        // Paginate the results
        $pagination = $query->paginate($filters['attendees_perPage'], ['*'], 'attendees_page', $filters['attendees_page']);

        // Prepare pagination information
        $paginationInfo = [
            'page' => $pagination->currentPage(),
            'perPage' => (int) $filters['attendees_perPage'],
            'total' => $pagination->total(),
            'lastPage' => $pagination->lastPage(),
        ];

        // Prepare sorting information
        $sortingInfo = [
            'field' => $filters['attendees_sortField'],
            'direction' => $filters['attendees_sortDirection'],
        ];

        // Prepare filter information
        $appliedFilters = [
            'search' => $filters['attendees_search'],
            'registrationDate' => [
                'operator' => $filters['attendees_created_at_operator'],
                'date' => $filters['attendees_created_at_date'],
                'dateEnd' => $filters['attendees_created_at_date_end'],
            ],
            'birthday' => [
                'operator' => $filters['attendees_birthday_operator'],
                'day' => $filters['attendees_birthday_day'],
                'month' => $filters['attendees_birthday_month'],
                'year' => $filters['attendees_birthday_year'],
                'endDay' => $filters['attendees_birthday_end_day'],
                'endMonth' => $filters['attendees_birthday_end_month'],
                'endYear' => $filters['attendees_birthday_end_year'],
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
