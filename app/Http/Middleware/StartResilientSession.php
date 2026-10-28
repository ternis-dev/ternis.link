<?php

namespace App\Http\Middleware;

use App\Http\Controllers\RedirectController;
use App\Support\RedirectResilience;
use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\Store;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;

class StartResilientSession extends StartSession
{
    public function handle($request, Closure $next)
    {
        $configuredDomain = config('session.domain');
        $domain = strtolower(ltrim((string) $configuredDomain, '.'));
        $host = strtolower($request->getHost());

        // A shared ternis.link cookie is invalid on href.nz and the other
        // shortener domains. Use host-only cookies there so the browser
        // retains the session and Livewire's CSRF token between requests.
        if ($domain !== '' && $host !== $domain && ! str_ends_with($host, '.'.$domain)) {
            config(['session.domain' => null]);
        }

        try {
            return parent::handle($request, $next);
        } finally {
            config(['session.domain' => $configuredDomain]);
        }
    }

    private function isRedirect(Request $request): bool
    {
        return ltrim((string) $request->route()?->getControllerClass(), '\\') === RedirectController::class;
    }

    protected function startSession(Request $request, $session)
    {
        if (! $this->isRedirect($request)) {
            return parent::startSession($request, $session);
        }

        return RedirectResilience::optional(
            fn () => parent::startSession($request, $session),
        ) ?? tap(new Store($session->getName(), new NullSessionHandler), fn ($fallback) => $fallback->start());
    }

    protected function collectGarbage(Session $session)
    {
        if ($this->isRedirect(request())) {
            RedirectResilience::optional(fn () => parent::collectGarbage($session));

            return;
        }

        parent::collectGarbage($session);
    }

    protected function saveSession($request)
    {
        if ($this->isRedirect($request)) {
            if (! $request->isPrecognitive()) {
                // Save the actual request session, including an empty fallback.
                RedirectResilience::optional(fn () => $request->session()->save());
            }

            return;
        }

        parent::saveSession($request);
    }
}
