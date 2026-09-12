<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Front/Contact', [
            'contactDefaults' => [
                'first_name' => $user?->first_name ?? '',
                'last_name' => $user?->last_name ?? '',
                'email' => $user?->email ?? '',
            ],
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $rateLimitKey = 'contact-form:' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return back()->withErrors([
                'message' => 'Trop de messages ont été envoyés. Veuillez réessayer dans quelques minutes.',
            ]);
        }

        RateLimiter::hit($rateLimitKey, 300);

        $data = $request->validated();

        Mail::to(config('mail.from.address'))->send(
            new ContactMail(
                contact: $data,
                isExistingMember: User::where('email', $data['email'])->exists(),
            ),
        );

        return back()->with('success', 'Votre message a bien été envoyé.');
    }
}
