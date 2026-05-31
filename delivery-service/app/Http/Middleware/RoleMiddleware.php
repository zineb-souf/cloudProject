<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!auth()->check()) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Vérifier le rôle de l'utilisateur
        if (auth()->user()->role !== $role) {
            return response()->json([
                'status' => false,
                'message' => 'Accès refusé. Rôle requis: ' . $role
            ], 403);
        }

        return $next($request);
    }
}
