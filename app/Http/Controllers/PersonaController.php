<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Area;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    /**
     * LISTADO DE PERSONAS CON FILTRO POR ÁREA
     */
    public function index(Request $request)
    {
        $areas = Area::all();
        $area_id = $request->input('area_id');

        $personas = Persona::with('area')
            ->when($area_id, function ($query) use ($area_id) {
                $query->where('area_id', $area_id);
            })
            ->orderBy('id', 'desc')
            ->paginate(5);

        return view('admin.personas.index', compact('personas', 'areas', 'area_id'));
    }

    /**
     * FORMULARIO CREAR PERSONA
     */
    public function create()
    {
        $areas = Area::all();

        return view('admin.personas.create', compact('areas'));
    }

    /**
     * GUARDAR PERSONA
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id_area',
            'plantilla_referencia' => 'required|string|max:255',
        ]);

        Persona::create($validated);

        return redirect('/personas')
            ->with('success', 'Persona registrada correctamente');
    }

    /**
     * EDITAR PERSONA
     */
    public function edit($id)
    {
        $persona = Persona::findOrFail($id);
        $areas = Area::all();

        return view('admin.personas.edit', compact('persona', 'areas'));
    }

    /**
     * ACTUALIZAR PERSONA
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id_area',
            'plantilla_referencia' => 'required|string|max:255',
        ]);

        $persona = Persona::findOrFail($id);

        $persona->update($validated);

        return redirect('/personas')
            ->with('success', 'Persona actualizada correctamente');
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