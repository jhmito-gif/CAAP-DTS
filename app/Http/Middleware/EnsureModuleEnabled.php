<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers "turned off" for the pages of a module an admin has switched off,
 * so an old bookmark or a link in a notification says what happened rather
 * than breaking.
 *
 * Usage on a route: ->middleware('module:esign')
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (Modules::enabled($module)) {
            return $next($request);
        }

        $label = Modules::CATALOGUE[$module]['label'] ?? 'This part of the system';

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$label} is turned off."], 404);
        }

        return response()->view('errors.module-off', ['label' => $label], 404);
    }
}
