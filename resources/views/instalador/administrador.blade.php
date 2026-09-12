<x-instalador.layout :paso="5" titulo="Cuenta del administrador"
    descripcion="Con esta cuenta se entra al sistema y se crean las demás: la secretaria, los lectores y los accesos de los vecinos.">

    <form method="POST" action="{{ route('instalador.instalar') }}">
        @csrf

        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $datos['nombre'] ?? '') }}" required>
        </div>

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email', $datos['email'] ?? '') }}" required>
            <span class="nota">Es con lo que va a iniciar sesión.</span>
        </div>

        <div class="dupla">
            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
                <span class="nota">Al menos 8 caracteres.</span>
            </div>

            <div class="campo">
                <label for="password_confirmation">Repita la contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
        </div>

        <div class="campo">
            <label class="casilla">
                <input type="checkbox" name="datos_de_ejemplo" value="1" @checked(old('datos_de_ejemplo'))>
                <span>
                    Cargar datos de ejemplo para probar el sistema
                    <span class="nota">
                        Crea un cliente y un lector de prueba con contraseñas conocidas. Déjelo sin
                        marcar si esta es la instalación real de la oficina.
                    </span>
                </span>
            </label>
        </div>

        <div class="resumen">
            <p>Al continuar se preparan las tablas y se carga la configuración inicial.
            Puede tardar algunos segundos; no cierre la ventana.</p>
        </div>

        <div class="acciones">
            <a class="boton discreto atras" href="{{ route('instalador.oficina') }}">Atrás</a>
            <button type="submit" class="boton">Instalar</button>
        </div>
    </form>
</x-instalador.layout>
