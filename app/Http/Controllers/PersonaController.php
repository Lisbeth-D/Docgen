<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    /**
     * LISTADO DE PERSONAS
     */
    public function index(Request $request)
    {
        $buscar = $request->input('buscar');

        $personas = Persona::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('nombre', 'LIKE', "%$buscar%")
                      ->orWhere('cargo', 'LIKE', "%$buscar%")
                      ->orWhere('area', 'LIKE', "%$buscar%");
            })
            ->orderBy('id', 'desc')
            ->paginate(5);

        return view('admin.personas.index', compact('personas', 'buscar'));
    }

    /**
     * FORMULARIO CREAR PERSONA
     */
    public function create()
    {
        return view('admin.personas.create');
    }

    /**
     * GUARDAR PERSONA
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo'  => 'required|string|max:255',
            'area'   => 'required|string|max:255',
        ]);

        Persona::create([
            'nombre' => $request->nombre,
            'cargo'  => $request->cargo,
            'area'   => $request->area,
        ]);

        return redirect('/personas')
            ->with('success', 'Persona registrada correctamente');
    }

    /**
     * EDITAR PERSONA
     */
    public function edit($id)
    {
        $persona = Persona::findOrFail($id);

        return view('admin.personas.edit', compact('persona'));
    }

    /**
     * ACTUALIZAR PERSONA
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required',
            'cargo' => 'required',
            'area' => 'required'
        ]);

        $persona = Persona::findOrFail($id);

        $persona->update([
            'nombre' => $request->nombre,
            'cargo' => $request->cargo,
            'area' => $request->area
        ]);

        return redirect('/personas')->with('success','Persona actualizada');
    }

    /**
     * ELIMINAR PERSONA
     */
    public function destroy($id)
    {
        $persona = Persona::findOrFail($id);
        $persona->delete();

        return back()->with('success', 'Persona eliminada correctamente');
    }
}