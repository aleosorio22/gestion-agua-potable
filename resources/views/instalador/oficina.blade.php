<x-instalador.layout :paso="4" titulo="Datos de la oficina"
    descripcion="Esto es lo que sale impreso en las boletas y recibos, y lo que se ve en el encabezado del sistema. Todo se puede cambiar después desde Configuración.">

    <form method="POST" action="{{ route('instalador.oficina.guardar') }}" enctype="multipart/form-data">
        @csrf

        <div class="campo">
            <label for="nombre">Nombre de la oficina</label>
            <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $datos['nombre'] ?? '') }}" required>
            <span class="nota">Como debe leerse en el recibo del vecino. Ej.: Oficina Municipal de Agua Potable.</span>
        </div>

        <div class="dupla">
            <div class="campo">
                <label for="municipio">Municipio</label>
                <input type="text" id="municipio" name="municipio" value="{{ old('municipio', $datos['municipio'] ?? '') }}">
            </div>

            <div class="campo">
                <label for="departamento">Departamento</label>
                <input type="text" id="departamento" name="departamento" value="{{ old('departamento', $datos['departamento'] ?? '') }}">
            </div>
        </div>

        <div class="dupla">
            <div class="campo">
                <label for="nit">NIT</label>
                <input type="text" id="nit" name="nit" value="{{ old('nit', $datos['nit'] ?? '') }}">
            </div>

            <div class="campo">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $datos['telefono'] ?? '') }}">
            </div>
        </div>

        <div class="campo">
            <label for="direccion">Dirección de la oficina</label>
            <input type="text" id="direccion" name="direccion" value="{{ old('direccion', $datos['direccion'] ?? '') }}">
        </div>

        <div class="campo">
            <label for="formato">Formato de papel</label>
            <select id="formato" name="formato" required>
                @foreach ($formatos as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(old('formato', $datos['formato'] ?? 'termica_80') === $valor)>
                        {{ $etiqueta }}
                    </option>
                @endforeach
            </select>
            <span class="nota">En qué papel se imprimen las boletas. Si la oficina tiene impresora de tickets, elija rollo térmico.</span>
        </div>

        <div class="campo">
            <label for="logo">Logotipo</label>
            <input type="file" id="logo" name="logo" accept="image/*">
            <span class="nota">Opcional, hasta 2 MB. Sale en el encabezado del sistema y de los documentos impresos.</span>
        </div>

        <div class="acciones">
            <a class="boton discreto atras" href="{{ route('instalador.base-de-datos') }}">Atrás</a>
            <button type="submit" class="boton">Continuar</button>
        </div>
    </form>
</x-instalador.layout>
