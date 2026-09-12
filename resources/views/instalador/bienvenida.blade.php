<x-instalador.layout :paso="1" titulo="Bienvenido"
    descripcion="Este asistente deja el sistema listo para usarse. Toma unos minutos y no hace falta saber de servidores.">

    <p>Durante la instalación se va a pedir:</p>

    <ul class="lista-clara">
        <li>Los datos de la base de datos donde se guardará todo.</li>
        <li>El nombre y los datos de la oficina, que salen impresos en las boletas.</li>
        <li>La cuenta del administrador con la que se entra al sistema por primera vez.</li>
    </ul>

    <div class="resumen">
        <p><strong>Antes de empezar</strong>, tenga a mano los datos de acceso a la base de datos.
        Si no los tiene, pídalos a quien administra el servidor.</p>
    </div>

    <div class="acciones">
        <a class="boton" href="{{ route('instalador.requisitos') }}">Comenzar</a>
    </div>
</x-instalador.layout>
