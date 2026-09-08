<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioClosureRequest;
use App\Models\StudioClosure;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Holidays\Holidays;
use Throwable;

class StudioClosureController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(['school_holiday', 'studio_closure'])],
            'period' => ['nullable', 'string', Rule::in(['all', 'upcoming', 'past'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim($validated['search'] ?? '');
        $type = $validated['type'] ?? '';
        $period = $validated['period'] ?? 'upcoming';
        $perPage = (int) ($validated['per_page'] ?? 25);
        $today = now('Europe/Brussels')->toDateString();

        $query = StudioClosure::query()
            ->when($search !== '', function ($query) use ($search) {
                // Traiter % et _ comme des caractères saisis, pas des jokers SQL.
                $literal = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);
                $query->whereRaw("name LIKE ? ESCAPE '!'", ['%'.$literal.'%']);
            })
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($period === 'upcoming', fn ($query) => $query->where('end_date', '>=', $today))
            ->when($period === 'past', fn ($query) => $query->where('end_date', '<', $today));

        // Prochaines dates en premier ; historique du plus récent au plus ancien.
        $direction = $period === 'past' ? 'desc' : 'asc';
        $query->orderBy('start_date', $direction)->orderBy('end_date', $direction)->orderBy('id');
        $closures = $query->paginate($perPage)->withQueryString();

        // Une suppression ou une modification peut vider la dernière page.
        if ($closures->currentPage() > $closures->lastPage()) {
            $closures = $query
                ->paginate($perPage, ['*'], 'page', $closures->lastPage())
                ->withQueryString();
        }

        $closures->through(function (StudioClosure $closure) use ($today) {
            $start = $closure->start_date->toDateString();
            $end = $closure->end_date->toDateString();

            return [
                'id' => $closure->id,
                'name' => $closure->name,
                'type' => $closure->type,
                'start_date' => $start,
                'end_date' => $end,
                'notes' => $closure->notes,
                'status' => $end < $today ? 'past' : ($start > $today ? 'upcoming' : 'ongoing'),
            ];
        });

        return Inertia::render('Admin/StudioClosures/Index', [
            'closures' => $closures,
            // Évaluation différée pour les recherches partielles Inertia.
            'publicHolidays' => fn () => $this->publicHolidays(),
            'filters' => [
                'search' => $search,
                'type' => $type,
                'period' => $period,
                'per_page' => $perPage,
            ],
        ]);
    }

    private function publicHolidays(): array
    {
        $currentYear = now('Europe/Brussels')->year;
        $years = [$currentYear, $currentYear + 1];
        $datesByYear = [];

        try {
            foreach ($years as $year) {
                $provider = Holidays::for(country: 'be', year: $year, locale: 'fr');
                $datesByYear[$year] = [];
                $date = CarbonImmutable::create($year, 1, 1, 0, 0, 0, 'Europe/Brussels');

                // Réutilise les méthodes déjà employées dans l’application.
                while ($date->year === $year) {
                    $iso = $date->toDateString();

                    if ($provider->isHoliday($iso)) {
                        $datesByYear[$year][] = [
                            'date' => $iso,
                            'name' => $provider->getName($iso) ?? 'Jour férié',
                        ];
                    }

                    $date = $date->addDay();
                }
            }
        } catch (Throwable $exception) {
            report($exception);

            return [
                'years' => $years,
                'by_year' => [],
                'error' => 'Les jours fériés ne sont pas disponibles pour le moment.',
            ];
        }

        return ['years' => $years, 'by_year' => $datesByYear, 'error' => null];
    }

    public function store(StudioClosureRequest $request): RedirectResponse
    {
        StudioClosure::create($request->validated());

        return back();
    }

    public function update(StudioClosureRequest $request, StudioClosure $studioClosure): RedirectResponse
    {
        $studioClosure->update($request->validated());

        return back();
    }

    public function destroy(Request $request, StudioClosure $studioClosure): RedirectResponse
    {
        $request->validate(
            ['confirmed' => ['accepted']],
            ['confirmed.accepted' => 'Cochez la confirmation pour supprimer cette période.']
        );

        $studioClosure->delete();

        return back();
    }
}
