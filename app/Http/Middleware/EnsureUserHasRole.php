<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if ($request->user() && ! $request->user()->hasAnyRole($roles)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
