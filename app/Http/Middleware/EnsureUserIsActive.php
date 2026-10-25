<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * CIMA-042 / security rule 10: deactivated accounts lose access immediately —
 * their session is destroyed on the next request and login is refused.
 * Sessions are also deleted at deactivation time by UserService.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null && $user->isDeactivated()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your account has been deactivated.'),
                ], 403);
            }

            return redirect()
                ->guest(route('login', absolute: false))
                ->with('status', __('Your account has been deactivated.'));
        }

        return $next($request);
    }
}
