<x-instalador.layout :paso="3" titulo="Base de datos"
    descripcion="Aquí se guardarán los clientes, las lecturas y los pagos. La base debe existir y estar vacía; el sistema crea sus propias tablas.">

    <form method="POST" action="{{ route('instalador.base-de-datos.probar') }}">
        @csrf

        <div class="dupla">
            <div class="campo">
                <label for="host">Servidor</label>
                <input type="text" id="host" name="host" value="{{ old('host', $datos['host'] ?? '127.0.0.1') }}" required>
                <span class="nota">Casi siempre 127.0.0.1 si la base está en la misma máquina.</span>
            </div>

            <div class="campo">
                <label for="puerto">Puerto</label>
                <input type="number" id="puerto" name="puerto" value="{{ old('puerto', $datos['puerto'] ?? '3306') }}" required>
                <span class="nota">El puerto de MySQL suele ser 3306.</span>
            </div>
        </div>

        <div class="campo">
            <label for="base">Nombre de la base de datos</label>
            <input type="text" id="base" name="base" value="{{ old('base', $datos['base'] ?? '') }}" required>
        </div>

        <div class="dupla">
            <div class="campo">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" value="{{ old('usuario', $datos['usuario'] ?? '') }}" required>
            </div>

            <div class="campo">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" value="{{ old('contrasena', $datos['contrasena'] ?? '') }}">
                <span class="nota">Déjela vacía si el usuario no tiene contraseña.</span>
            </div>
        </div>

        <div class="acciones">
            <a class="boton discreto atras" href="{{ route('instalador.requisitos') }}">Atrás</a>
            <button type="submit" class="boton">Probar y continuar</button>
        </div>
    </form>
</x-instalador.layout>
