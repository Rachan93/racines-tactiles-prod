<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;

class HandleControllerPrecognitiveRequest extends HandlePrecognitiveRequests
{
    /**
     * Handle an incoming request.
     */
   public function handle($request, $next)
{
    if (! $request->routeIs(
        'register.store',
        'user-profile-information.update'
    )) {
        return $next($request);
    }

    return parent::handle($request, $next);
}

    /**
     * Permet à Fortify d'exécuter CreateNewUser pendant
     * une requête Precognition.
     */
    protected function prepareForPrecognition($request)
    {
        parent::prepareForPrecognition($request);

        $this->container->bind(
            CallableDispatcherContract::class,
            fn ($app) => new CallableDispatcher($app)
        );

        $this->container->bind(
            ControllerDispatcherContract::class,
            fn ($app) => new ControllerDispatcher($app)
        );
    }
}
