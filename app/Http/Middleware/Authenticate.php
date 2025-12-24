<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Vérifie que l'utilisateur est authentifié.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if ($request->user() === null) {
            // Si la requête attend du JSON (API), renvoie une erreur 401
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }

            // Sinon, redirige vers la page login (optionnel)
            return redirect()->route('login');
        }

        return $next($request);
    }
}
