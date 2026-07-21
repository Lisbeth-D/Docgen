<?php

namespace App\Http\Controllers;

use App\Models\DocumentoAdjudicacion;
use Illuminate\Http\Request;

class DocumentoAdjudicacionController extends Controller
{
    public function index()
    {
        $documentos = DocumentoAdjudicacion::query()
            ->orderBy('orden')
            ->orderBy('id_documento')
            ->paginate(15);

        return view('comprador.registros.documentos.index', compact('documentos'));
    }

    public function create()
    {
        $siguienteOrden = ((int) DocumentoAdjudicacion::max('orden')) + 1;

        return view('comprador.registros.documentos.create', compact('siguienteOrden'));
    }

    public function store(Request $request)
    {
        DocumentoAdjudicacion::create($this->validarDocumento($request));

        return redirect()
            ->route('comprador.registros.documentos.index')
            ->with('success', 'Documento registrado correctamente.');
    }

    public function edit(DocumentoAdjudicacion $documento)
    {
        return view('comprador.registros.documentos.edit', compact('documento'));
    }

    public function update(Request $request, DocumentoAdjudicacion $documento)
    {
        $documento->update($this->validarDocumento($request));

        return redirect()
            ->route('comprador.registros.documentos.index')
            ->with('success', 'Documento actualizado correctamente.');
    }

    public function cambiarEstado(DocumentoAdjudicacion $documento)
    {
        $documento->update(['activo' => !$documento->activo]);

        return back()->with(
            'success',
            $documento->activo
                ? 'Documento activado correctamente.'
                : 'Documento desactivado correctamente.'
        );
    }

    public function destroy(DocumentoAdjudicacion $documento)
    {
        $documento->delete();

        return back()->with('success', 'Documento eliminado correctamente.');
    }

    private function validarDocumento(Request $request): array
    {
        $datos = $request->validate(
            [
                'nombre' => ['required', 'string', 'max:255'],
                'leyenda' => ['required', 'string', 'max:5000'],
                'orden' => ['required', 'integer', 'min:1', 'max:9999'],
                'activo' => ['nullable', 'boolean'],
                'obligatorio' => ['nullable', 'boolean'],
            ],
            [
                'nombre.required' => 'El nombre del documento es obligatorio.',
                'nombre.max' => 'El nombre no debe exceder los 255 caracteres.',
                'leyenda.required' => 'La leyenda para el Word es obligatoria.',
                'leyenda.max' => 'La leyenda no debe exceder los 5,000 caracteres.',
                'orden.required' => 'El orden es obligatorio.',
                'orden.integer' => 'El orden debe ser un número entero.',
                'orden.min' => 'El orden debe ser mayor o igual a 1.',
            ]
        );

        $datos['activo'] = $request->boolean('activo');
        $datos['obligatorio'] = $request->boolean('obligatorio');

        return $datos;
    }
}
