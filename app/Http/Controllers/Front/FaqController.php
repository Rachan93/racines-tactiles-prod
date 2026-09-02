<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __invoke(): Response
    {
        $faqs = Faq::query()
            ->published()
            ->ordered()
            ->get([
                'id',
                'question',
                'answer',
            ]);

        return Inertia::render('Front/Faq', [
            'faqs' => $faqs,
        ]);
    }
}
