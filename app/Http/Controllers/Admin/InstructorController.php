<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstructorRequest;
use App\Models\Instructor;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InstructorController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['staff', 'external'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim($filters['search'] ?? '');
        $type = $filters['type'] ?? '';
        $perPage = (int) ($filters['per_page'] ?? 25);

        $instructors = Instructor::query()
            ->when($search !== '', function ($query) use ($search) {
                // Chaque mot peut correspondre au prénom ou au nom.
                $words = preg_split(
                    '/\s+/u',
                    $search,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );

                foreach ($words as $word) {
                    $query->where(function ($names) use ($word) {
                        $names
                            ->where('first_name', 'like', '%'.$word.'%')
                            ->orWhere('last_name', 'like', '%'.$word.'%');
                    });
                }
            })
            ->when(
                $type !== '',
                fn ($query) => $query->where('type', $type)
            )
            ->withCount(['courses', 'lessons'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Instructors/Index', [
            'instructors' => $instructors,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    public function store(InstructorRequest $request): RedirectResponse
    {
        Instructor::create($request->validated());

        return back();
    }

    public function update(
        InstructorRequest $request,
        Instructor $instructor
    ): RedirectResponse {
        $instructor->update($request->validated());

        return back();
    }

    public function destroy(Instructor $instructor): RedirectResponse
    {
        $message = 'Cet instructeur est lié à un cours ou à une séance. '
            .'Sa fiche ne peut pas être supprimée.';

        try {
            DB::transaction(function () use ($instructor, $message) {
                $locked = Instructor::whereKey($instructor->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $locked->courses()->exists()
                    || $locked->lessons()->exists()
                ) {
                    throw ValidationException::withMessages([
                        'instructor' => $message,
                    ]);
                }

                $locked->delete();
            });
        } catch (QueryException $exception) {
            // Une référence peut avoir été ajoutée entre-temps.
            $driverCode = (string) ($exception->errorInfo[1] ?? '');
            $sqlState = (string) $exception->getCode();

            if (
                in_array($driverCode, ['1451', '547'], true)
                || $sqlState === '23503'
            ) {
                throw ValidationException::withMessages([
                    'instructor' => $message,
                ]);
            }

            throw $exception;
        }

        return back();
    }
}
