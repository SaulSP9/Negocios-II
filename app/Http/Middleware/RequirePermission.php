<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        abort_unless($request->user()?->hasPermission($permission), 403, 'Tu rol no tiene permiso para esta operación.');

        return $next($request);
    }
}
