<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    /**
     * Liste et gestion des FAQ.
     */
    public function index(): Response
    {
        $faqs = Faq::query()
            ->ordered()
            ->get()
            ->map(fn (Faq $faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'position' => $faq->position,
                'is_active' => $faq->is_active,

                'created_at' => $faq->created_at
                    ?->translatedFormat('d M Y'),

                'updated_at' => $faq->updated_at
                    ?->translatedFormat('d M Y à H:i'),
            ]);

        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => $faqs,

            'stats' => [
                'total' => $faqs->count(),
                'active' => $faqs
                    ->where('is_active', true)
                    ->count(),

                'inactive' => $faqs
                    ->where('is_active', false)
                    ->count(),
            ],
        ]);
    }

    /**
     * Création.
     */
    public function store(FaqRequest $request): RedirectResponse
    {
        Faq::create($request->validated());

        return redirect()
            ->back()
            ->with(
                'success',
                'La question a été ajoutée à la FAQ.'
            );
    }

    /**
     * Modification.
     */
    public function update(
        FaqRequest $request,
        Faq $faq
    ): RedirectResponse {
        $faq->update($request->validated());

        return redirect()
            ->back()
            ->with(
                'success',
                'La question a été mise à jour.'
            );
    }

    /**
     * Suppression.
     */
    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                'La question a été supprimée.'
            );
    }
}
