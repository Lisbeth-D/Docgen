<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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

        // Buscar usuario
        $user = User::where('username', $request->username)->first();

        // Usuario no existe
        if (!$user) {
            throw ValidationException::withMessages([
                'username' => 'Las credenciales no son correctas.',
            ]);
        }

        // Usuario desactivado
        if (!$user->activo) {
            throw ValidationException::withMessages([
                'username' => 'La cuenta no tiene permisos activos. Comuníquese con el administrador del sistema.',
            ]);
        }

        // Validar contraseña
        if (!Auth::attempt(
            [
                'username' => $request->username,
                'password' => $request->password,
            ],
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'username' => 'Las credenciales no son correctas.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Redirección según rol
        if ($user->role === 'admin') {
            return redirect()->intended('/admin/dashboard');
        }

        if ($user->role === 'comprador') {
            return redirect()->intended('/dashboard');
        }

        // Rol inválido
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