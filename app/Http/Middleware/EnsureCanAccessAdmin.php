<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate-keeps the whole /admin area. Individual modules are still authorised
 * by their own policies — this only decides who may see the panel at all.
 */
class EnsureCanAccessAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canAccessAdmin(), 403, 'You do not have access to the administration area.');

        return $next($request);
    }
}
