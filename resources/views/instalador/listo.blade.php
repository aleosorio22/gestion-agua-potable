<x-instalador.layout :paso="6" titulo="El sistema está listo"
    descripcion="La instalación terminó. Ya puede entrar y empezar a registrar clientes.">

    @if ($correo)
        <dl class="credenciales">
            <dt>Entre con</dt>
            <dd>{{ $correo }}</dd>
            <dt>Contraseña</dt>
            <dd>La que acaba de elegir</dd>
        </dl>
    @endif

    <p>Lo primero que conviene hacer:</p>

    <ul class="lista-clara">
        <li>Revisar las <strong>tarifas</strong> y las <strong>pajas</strong>, que definen cuánto se cobra.</li>
        <li>Registrar a los <strong>clientes</strong> con sus contadores.</li>
        <li>Crear las cuentas de la <strong>secretaria</strong> y de los <strong>lectores</strong>.</li>
    </ul>

    <div class="acciones">
        <a class="boton" href="/admin">Entrar al sistema</a>
    </div>
</x-instalador.layout>
