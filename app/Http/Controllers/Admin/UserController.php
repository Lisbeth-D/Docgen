<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function index(Request $request)
    {

        $buscar = $request->buscar;

        $users = User::where('name','LIKE',"%$buscar%")
            ->orWhere('username','LIKE',"%$buscar%")
            ->orWhere('email','LIKE',"%$buscar%")
            ->paginate(5);

        return view('admin.usuarios.index', compact('users','buscar'));

    }

    public function create()
    {
        return view('admin.usuarios.create');
    }

    public function store(Request $request)
    {

    $request->validate([

    'name'=>'required',
    'username'=>'required|unique:users',
    'email'=>'required|email|unique:users',
    'password'=>'required|min:6',
    'role'=>'required'

    ]);


    User::create([

    'name'=>$request->name,
    'username'=>$request->username,
    'email'=>$request->email,
    'password'=>Hash::make($request->password),
    'role'=>$request->role

    ]);


    return redirect('/usuarios')->with('success','Usuario creado correctamente');

    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return redirect('/usuarios')->with('success','Usuario eliminado');
    }

    public function resetPassword($id)
    {

    $user = User::findOrFail($id);

    $user->password = Hash::make('12345678');

    $user->save();

    return back()->with('success','Contraseña reseteada');

    }

    public function toggleActivo($id)
    {

    $user = User::findOrFail($id);

    $user->activo = !$user->activo;

    $user->save();

    return back();

    }

    public function actividad()
   {
        $totalUsuarios = User::count();
        $totalPersonas = Persona::count();

        return view('admin.reportes.actividad', compact(
            'totalUsuarios',
            'totalPersonas'
        ));
    }

    /**
 * FORMULARIO EDITAR USUARIO
 */
    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view('admin.usuarios.edit', compact('user'));
    }

    /**
     * ACTUALIZAR USUARIO
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,username,' . $id,
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required'
    ]);

    $user = User::findOrFail($id);

    $user->update([
        'name' => $request->name,
        'username' => $request->username,
        'email' => $request->email,
        'role' => $request->role
    ]);

    return redirect('/usuarios')->with('success','Usuario actualizado');
    }

}