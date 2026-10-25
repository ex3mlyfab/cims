<?php

namespace App\Http\Middleware;

use App\Models\HospitalBranding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branding = HospitalBranding::resolveForRequest();

        View::share('appearance', $request->cookie('appearance') ?? 'system');

        // Theme precedence (ADR-005): user's manual choice first, then the
        // hospital branding default. User preferences are never overwritten.
        View::share(
            'themePreset',
            $request->cookie('cima_theme_preset') ?? $branding?->defaultPreset(),
        );

        // Hospital branding values for non-Inertia blade surfaces (title).
        View::share('brandingName', $branding?->name);

        return $next($request);
    }
}
