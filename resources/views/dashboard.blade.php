@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('content')

    <div class="container-fluid p-0">

        <div class="mb-4">
            <h4 class="fw-semibold mb-1">
                Bienvenido 👋
            </h4>

            <p class="text-muted mb-0">
                Aquí tienes un resumen de tu sistema.
            </p>
        </div>


        <div class="row g-4">

            <div class="col-12 col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>
                                <p class="text-muted small mb-1">
                                    Usuarios
                                </p>

                                <h3 class="fw-bold mb-0">
                                    125
                                </h3>
                            </div>

                            <div class="text-primary fs-3">
                                <i class="bi bi-people"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-12 col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>
                                <p class="text-muted small mb-1">
                                    Productos
                                </p>

                                <h3 class="fw-bold mb-0">
                                    350
                                </h3>
                            </div>

                            <div class="text-primary fs-3">
                                <i class="bi bi-box"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-12 col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>
                                <p class="text-muted small mb-1">
                                    Ventas
                                </p>

                                <h3 class="fw-bold mb-0">
                                    Q 12,500
                                </h3>
                            </div>

                            <div class="text-primary fs-3">
                                <i class="bi bi-graph-up"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-12 col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>
                                <p class="text-muted small mb-1">
                                    Pedidos
                                </p>

                                <h3 class="fw-bold mb-0">
                                    48
                                </h3>
                            </div>

                            <div class="text-primary fs-3">
                                <i class="bi bi-cart"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection