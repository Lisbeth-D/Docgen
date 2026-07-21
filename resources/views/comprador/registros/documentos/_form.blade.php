@csrf

<div class="conv-grid">
    <div class="conv-group full">
        <label for="nombre">Nombre corto del documento</label>
        <input type="text" id="nombre" name="nombre"
            value="{{ old('nombre', $documento->nombre ?? '') }}"
            class="@error('nombre') input-error @enderror">
        @error('nombre')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="conv-group full">
        <label for="leyenda">Leyenda que aparecerá en el Word</label>
        <textarea id="leyenda" name="leyenda" rows="7"
            class="@error('leyenda') input-error @enderror">{{ old('leyenda', $documento->leyenda ?? '') }}</textarea>
        <small class="form-help">No escriba a), b), c). El sistema genera el inciso automáticamente.</small>
        @error('leyenda')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="conv-group">
        <label for="orden">Orden</label>
        <input type="number" id="orden" name="orden" min="1"
            value="{{ old('orden', $documento->orden ?? $siguienteOrden ?? 1) }}"
            class="@error('orden') input-error @enderror">
        @error('orden')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="conv-group">
        <label class="check-option">
            <input type="checkbox" name="activo" value="1"
                @checked(old('activo', $documento->activo ?? true))>
            Activo
        </label>

        <label class="check-option">
            <input type="checkbox" name="obligatorio" value="1"
                @checked(old('obligatorio', $documento->obligatorio ?? false))>
            Obligatorio
        </label>
    </div>
</div>

<div class="form-actions">
    <a href="{{ route('comprador.registros.documentos.index') }}" class="btn-cancel">Cancelar</a>
    <button type="submit" class="conv-btn">{{ $textoBoton }}</button>
</div>
