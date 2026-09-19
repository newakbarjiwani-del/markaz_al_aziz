<?php

namespace App\Http\Middleware;

use App\Support\AdminModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    /**
     * Allow /admin when the user has an admin-shell role or any assignable module gate permission.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && AdminModuleAccess::userCanAccessAdminShell($user)) {
            return $next($request);
        }

        abort(403);
    }
}
