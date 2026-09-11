{{--
    Ajustes de campo, inyectados en el head del panel del lector.

    Van como CSS suelto y no como tema compilado a propósito: son cuatro reglas
    de tamaño y contraste, y meterlas en un tema obligaría a recompilar assets
    para cambiar un tamaño de letra.
--}}
<style>
    /* La lista se lee a un brazo de distancia y con sol de frente. */
    .fi-ta-table,
    .fi-ta-record-content {
        font-size: 1.0625rem;
        line-height: 1.6;
    }

    .fi-ta-cell {
        padding-top: .9rem;
        padding-bottom: .9rem;
    }

    /* Objetivos de toque cómodos con una mano: 44 px es el mínimo que se
       acierta sin mirar, que es como se usa esto. */
    .fi-btn,
    .fi-ta-actions .fi-btn,
    .fi-tabs-item {
        min-height: 2.75rem;
        font-size: 1rem;
    }

    /* El campo de la lectura es el único que se teclea en toda la jornada. */
    .fi-fo-field-wrp input[type="number"],
    .fi-fo-field-wrp input[inputmode="decimal"] {
        font-size: 1.375rem;
        font-weight: 600;
        letter-spacing: .02em;
        text-align: right;
        padding-top: .75rem;
        padding-bottom: .75rem;
    }

    /* El buscador por código, que es como el lector llega a cada casa. */
    .fi-ta-search-field input {
        font-size: 1.0625rem;
        padding-top: .7rem;
        padding-bottom: .7rem;
    }

    .fi-modal-window {
        --modal-padding: 1.25rem;
    }
</style>
