<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Mostrar vista de login
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesar inicio de sesión
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt(
            [
                'username' => $request->username,
                'password' => $request->password,
                'activo' => 1
            ],
            $request->boolean('remember')
        ))
        {
            throw ValidationException::withMessages([
                'username' => __('Las credenciales no son correctas.'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Redirección según rol
        $user = Auth::user();

        if(!$user->activo){

        Auth::logout();

        return redirect('/')->withErrors([
        'username'=>'Usuario desactivado'
        ]);

        }

        if ($user->role === 'admin') {
            return redirect()->intended('/admin/dashboard');
        }

        return redirect()->intended('/dashboard');

        if ($user->role === 'comprador') {
            return redirect('/dashboard');
        }

        // Si el rol no existe
        Auth::logout();

        return redirect('/')->withErrors([
            'username' => 'El usuario no tiene un rol válido.',
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}