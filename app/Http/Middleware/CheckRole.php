<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (!Auth::check()) {
            return redirect('/');
        }

        if (Auth::user()->role != $role) {
            abort(403, 'No tienes permiso para acceder.');
        }

        return $next($request);
    }
}