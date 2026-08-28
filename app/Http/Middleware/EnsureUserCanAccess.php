<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if ($request->user() && ! $request->user()->canAccess($ability)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
