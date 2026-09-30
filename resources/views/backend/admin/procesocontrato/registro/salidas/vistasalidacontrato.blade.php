@extends('adminlte::page')

@section('title', 'Registro de Salidas')

@section('content_header')
    <h1>Registro de Salidas</h1>
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">

    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">
                {{ Auth::guard('admin')->user()->nombre }}
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <a href="{{ route('admin.perfil') }}" class="dropdown-item">
                <i class="fas fa-user mr-2"></i>Editar Perfil
            </a>
        </div>
    </li>

    <li class="nav-item">
        <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="nav-link btn btn-link border-0 bg-transparent">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-md-inline">Cerrar Sesión</span>
            </button>
        </form>
    </li>
@endsection

@section('content')

    <style>
        table { table-layout: fixed; }

        .cursor-pointer:hover {
            cursor: pointer;
            color: #401fd2;
            font-weight: bold;
        }

        *:focus { outline: none; }

        .seccion-header {
            background: linear-gradient(135deg, #1a3a6b 0%, #2156af 100%);
            border-radius: 10px 10px 0 0;
            padding: 12px 18px;
        }
        .seccion-header h3 {
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            margin: 0;
        }

        .card-info {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 18px rgba(33,86,175,.13);
            margin-bottom: 20px;
        }
        .card-info .card-body { padding: 22px 24px; }

        .field-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7a99;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 5px;
            display: block;
        }

        .divider-azul {
            border: none;
            border-top: 2px solid #e8eef8;
            margin: 18px 0;
        }

        #matriz thead tr th {
            background: #2156af;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            border: none !important;
            padding: 10px 12px;
            white-space: nowrap;
        }
        #matriz tbody tr { transition: background .15s; }
        #matriz tbody tr:hover { background: #eef3ff !important; }
        #matriz tbody td { vertical-align: middle; font-size: 13px; padding: 8px 10px; }

        #matriz tfoot tr th {
            background: #eef3ff;
            color: #1a3a6b;
            font-size: 13px;
            padding: 10px 12px;
            border-top: 2px solid #2156af;
        }

        .btn-guardar-salida {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
            font-weight: 400;
            font-size: 14px;
            letter-spacing: .03em;
            box-shadow: 0 4px 14px rgba(40,167,69,.35);
            transition: all .2s;
        }
        .btn-guardar-salida:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(40,167,69,.45);
            color: #fff;
        }

        #tabla-detalle-contrato tr.fila-agotada td { color: #b02a37; font-weight: 700; }

        /* Modal de información: tabla con ancho automático para que quepa la columna de unidades */
        #tabla-info-contrato { table-layout: auto; }
        .badge-unidad { font-size: 12px; margin: 1px 0; display: inline-block; }

        /* Resultados del buscador */
        .droplista .list-group { max-height: 260px; overflow-y: auto; box-shadow: 0 4px 14px rgba(0,0,0,.15); }
        .droplista .list-group-item small { color: #6b7a99; }
    </style>

    <div id="divcontenedor" style="display: none">

        {{-- ══ SECCIÓN: INFORMACIÓN ══ --}}
        <section class="content" style="margin-bottom: 0">
            <div class="container-fluid">
                <div class="card card-info" style="border-radius:10px">

                    <div class="seccion-header">
                        <h3><i class="fas fa-info-circle mr-2"></i>Información de Retiro</h3>
                    </div>

                    <div class="card-body">

                        {{-- Fila 1: Fecha + No. Factura + Fecha Factura --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-calendar-alt mr-1"></i>Fecha</label>
                                    <input type="date" class="form-control" id="fecha">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-file-invoice mr-1"></i>No. Factura
                                        <small style="text-transform:none; font-weight:400">(Opc.)</small>
                                    </label>
                                    <input type="text" class="form-control" autocomplete="off" maxlength="100" id="no_factura" placeholder="Ej: 00123">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-calendar-check mr-1"></i>Fecha Factura
                                        <small style="text-transform:none; font-weight:400">(Opc.)</small>
                                    </label>
                                    <input type="date" class="form-control" id="fecha_factura">
                                </div>
                            </div>
                        </div>

                        {{-- Fila 2: Contrato + Unidad + Ver Información --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-file-contract mr-1"></i>Contrato / Proceso</label>
                                    <select class="form-control" id="select-contrato">
                                        <option value="0" selected disabled>Seleccionar Contrato</option>
                                        @foreach($arrayContratos as $id => $texto)
                                            <option value="{{ $id }}">{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-building mr-1"></i>Unidad de Origen</label>
                                    <select class="form-control" id="select-unidad">
                                        <option value="0" selected disabled>Seleccionar Unidad</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" id="botonInfoContrato" onclick="verInfoContrato()"
                                        class="btn btn-info btn-block" disabled
                                        style="border-radius:6px; margin-bottom:16px; font-weight:400">
                                    <i class="fas fa-info-circle mr-1"></i> Ver Información
                                </button>
                            </div>
                        </div>

                        {{-- Fila 3: Check otra unidad + Select destino --}}
                        <div class="row">
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="custom-control custom-checkbox mb-3">
                                    <input type="checkbox" class="custom-control-input" id="check-otra-unidad">
                                    <label class="custom-control-label" for="check-otra-unidad" style="font-weight:600">
                                        Es para otra unidad
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-8" id="contenedor-otra-unidad" style="display:none">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-share mr-1"></i>Unidad Destino</label>
                                    <select class="form-control" id="select-otra-unidad">
                                        <option value="0" selected disabled>Seleccionar Unidad Destino</option>
                                        @foreach($arrayDepartamentos as $dep)
                                            <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="divider-azul">

                        {{-- Fila 4: Descripción + Botón --}}
                        <div class="row align-items-end">
                            <div class="col-md-8 mb-2">
                                <label class="field-label">
                                    <i class="fas fa-align-left mr-1"></i>Descripción
                                    <small id="texto-descripcion-opc" style="text-transform:none; font-weight:400">(Opcional)</small>
                                </label>
                                <input type="text" class="form-control" autocomplete="off"
                                       maxlength="800" id="descripcion" placeholder="Descripción del retiro…">
                            </div>
                            <div class="col-md-4 mb-2 d-flex justify-content-end">
                                <button type="button" id="botonaddmaterial" onclick="abrirModal()"
                                        class="btn btn-primary btn-sm"
                                        disabled
                                        style="border-radius:6px; font-weight:400">
                                    <i class="fas fa-search mr-1"></i> Buscar Material
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        {{-- ══ SECCIÓN: DETALLE ══ --}}
        <section class="content">
            <div class="container-fluid">
                <div class="card card-info">

                    <div class="seccion-header" style="border-radius:10px 10px 0 0; display:flex; justify-content:space-between; align-items:center">
                        <h3><i class="fas fa-list mr-2"></i>Detalle de Retiro</h3>
                        <span id="contador-filas" style="background:rgba(255,255,255,.2); color:#fff; border-radius:20px; padding:2px 12px; font-size:12px; font-weight:700">
                            0 ítems
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="matriz"
                                   style="table-layout:fixed; width:100%; min-width:900px">
                                <thead>
                                <tr>
                                    <th style="width:4%">#</th>
                                    <th style="width:24%">Material</th>
                                    <th style="width:10%">U/M</th>
                                    <th style="width:18%">Unidad de Origen</th>
                                    <th style="width:11%">Precio</th>
                                    <th style="width:11%">Cantidad</th>
                                    <th style="width:12%">Subtotal</th>
                                    <th style="width:10%">Opciones</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="5" class="text-right">Totales</th>
                                    <th id="total-cantidad">0</th>
                                    <th id="total-subtotal">$0.00</th>
                                    <th></th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mt-3" style="margin: 10px; padding-bottom: 15px">
                        <button type="button"
                                id="botonGuardar"
                                class="btn-guardar-salida"
                                style="border-radius:6px; padding:6px 14px; font-size:12px"
                                onclick="preguntaGuardar()">
                            <i class="fas fa-save mr-1"></i>Guardar
                        </button>
                    </div>

                </div>
            </div>
        </section>

        {{-- ══ MODAL: BUSCAR MATERIAL ══ --}}
        <div class="modal fade" id="modalRepuesto">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background:#2156af">
                        <h4 class="modal-title" style="color:#fff"><i class="fas fa-search mr-2"></i>Buscar Material</h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-repuesto" onsubmit="return false;">
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="field-label">
                                        Material — Regresa: Nombre / U/M / Disponible en la unidad
                                        <span class="badge badge-success ml-1">Solo items asignados a la unidad de origen</span>
                                    </label>
                                    <table class="table" id="matriz-busqueda">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input id="inputBuscador" autocomplete="off"
                                                       class="form-control" style="width:100%"
                                                       onkeyup="buscarMaterial(this)"
                                                       onfocus="buscarMaterial(this)"
                                                       onclick="event.stopPropagation()"
                                                       maxlength="300" type="text"
                                                       placeholder="Escribir nombre del material o dejar vacío para ver todos…">
                                                <div class="droplista" id="midropmenu"
                                                     style="position:absolute; z-index:9; width:95% !important"></div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="tablaRepuesto"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: CANTIDAD / RETIRO DE MATERIAL ══ --}}
        <div class="modal fade" id="modalCantidad">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background:#1a3a6b">
                        <h4 class="modal-title" style="color:#fff"><i class="fas fa-boxes mr-2"></i>Retiro de Material</h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-material" onsubmit="return false;">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">

                                        <input type="hidden" id="id-material-seleccionado">
                                        <input type="hidden" id="id-unidad-seleccionada">
                                        <input type="hidden" id="precio-num-seleccionado">

                                        <div class="form-row mb-3">
                                            <div class="col-md-6">
                                                <label class="field-label">Material</label>
                                                <input type="text" disabled class="form-control" id="info-material">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="field-label">Unidad de Origen</label>
                                                <input type="text" disabled class="form-control" id="info-unidad-origen">
                                            </div>
                                        </div>

                                        <div class="form-row mb-3">
                                            <div class="col-md-6">
                                                <label class="field-label">Unidad de Medida</label>
                                                <input type="text" disabled class="form-control" id="info-unidad">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="field-label">Precio Unitario</label>
                                                <input type="text" disabled class="form-control" id="info-medida">
                                            </div>
                                        </div>

                                        <hr class="divider-azul">

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm" id="matrizM">
                                                <thead>
                                                <tr>
                                                    <th class="text-center">Asignado a la unidad</th>
                                                    <th class="text-center">Ya retirado</th>
                                                    <th class="text-center">Disponible</th>
                                                    <th style="width:30%">Cantidad a Retirar</th>
                                                </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button"
                                class="btn btn-success"
                                style="font-weight:400; border-radius:6px"
                                onclick="agregarAlDetalle()">
                            <i class="fas fa-plus mr-1"></i> Agregar al Detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: INFORMACIÓN DEL CONTRATO ══ --}}
        <div class="modal fade" id="modalInfoContrato">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background:#1a3a6b">
                        <h4 class="modal-title" style="color:#fff"><i class="fas fa-file-contract mr-2"></i>Información del Contrato</h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <div class="row mb-2">

                            <div class="col-md-6 mb-3">
                                <span class="field-label d-block">Código</span>
                                <span id="info-codigo">-</span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <span class="field-label d-block">Proveedor</span>
                                <span id="info-proveedor">-</span>
                            </div>

                            <div class="col-md-12 mb-3">
                                <span class="field-label d-block">Nombre</span>
                                <span id="info-nombre">-</span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <span class="field-label d-block">Fecha Inicio</span>
                                <span id="info-fecha-inicio">-</span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <span class="field-label d-block">Fecha Fin</span>
                                <span id="info-fecha-fin">-</span>
                            </div>

                            <div class="col-md-12 mb-3">
                                <span class="field-label d-block">Descripción</span>
                                <span id="info-descripcion">-</span>
                            </div>

                        </div>

                        <hr class="divider-azul">

                        <p class="mb-1">
                            <span class="field-label d-inline">Mostrando:</span>
                            <span id="info-filtro" class="badge badge-primary" style="font-size:12px">Todas las unidades</span>
                        </p>

                        <p class="text-muted mb-2" style="font-size:12px">
                            En <b>Unidades</b> se listan las unidades asignadas a cada material.
                            La unidad de origen seleccionada aparece resaltada en azul.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" id="tabla-info-contrato">
                                <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>U/M</th>
                                    <th id="th-contratado">Contratado</th>
                                    <th id="th-retirado">Retirado</th>
                                    <th id="th-disponible">Disponible</th>
                                    <th>Precio</th>
                                    <th>Unidades</th>
                                </tr>
                                </thead>
                                <tbody id="tabla-detalle-contrato"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- fin #divcontenedor --}}

@stop
@section('js')

    <script src="{{ asset('js/jquery.dataTables.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/bootstrap-input-spinner.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/custom-editors.js') }}" type="text/javascript"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            document.getElementById("divcontenedor").style.display = "block";

            var hoy = new Date();
            document.getElementById('fecha').value = hoy.toJSON().slice(0, 10);

            window.guardando = false;
            window.materialesBusqueda = {};
            window.buscadorTimer = null;
            window.buscadorSeq = 0;

            $(document).click(function () { $(".droplista").hide(); });

            // Al abrir el buscador se enfoca y lista los materiales de la unidad
            $('#modalRepuesto').on('shown.bs.modal', function () {
                $('#inputBuscador').trigger('focus');
            });

            // Al abrir el modal de cantidad se enfoca el input
            $('#modalCantidad').on('shown.bs.modal', function () {
                $('#input-cantidad-retiro').trigger('focus');
            });

            var configSelect2 = {
                theme: "bootstrap-5",
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            };

            $('#select-contrato').select2(configSelect2);
            $('#select-unidad').select2(configSelect2);
            $('#select-otra-unidad').select2(configSelect2);

            // ── Cambio de contrato: limpia el detalle y carga las unidades del contrato ──
            $('#select-contrato').on('change', function () {
                var val = $(this).val();
                var habilitar = !!val && val !== '0';

                $('#botonInfoContrato').prop('disabled', !habilitar);
                $('#botonaddmaterial').prop('disabled', true);

                limpiarDetalle();
                cargarUnidades(val, habilitar);

                $('#select-contrato').select2('close');
            });

            // ── Cambio de unidad: NO toca el detalle. Solo define de qué unidad es el próximo material ──
            // "Todos" sirve para consultar la información, pero no para retirar
            $('#select-unidad').on('change', function () {
                var val = $(this).val();

                $('#botonaddmaterial').prop('disabled', !unidadEspecifica(val));
                sincronizarDestino();
            });

            // ── Check "Es para otra unidad" ───────────────────────────────────
            $('#check-otra-unidad').on('change', function () {
                if (this.checked) {
                    sincronizarDestino();
                    $('#contenedor-otra-unidad').slideDown(150);
                    $('#texto-descripcion-opc').text('(Requerida: indique el motivo)').css('color', '#dc3545');
                } else {
                    $('#contenedor-otra-unidad').slideUp(150);
                    $('#select-otra-unidad').val('0').trigger('change');
                    $('#texto-descripcion-opc').text('(Opcional)').css('color', '');
                }
            });
        });
    </script>

    <script>

        // ── Helper: escapar texto para insertarlo en HTML ─────────────────
        function esc(texto) {
            return String(texto === null || texto === undefined ? '' : texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // ── Helper: ¿es una unidad concreta? (no vacío, no placeholder, no "Todos") ──
        function unidadEspecifica(val) {
            return !!val && val !== '0' && val !== 'todos';
        }

        // ── Helper: ids de las unidades de origen que ya están en el detalle ──
        function origenesEnDetalle() {
            return $("input[name='idmaterialArray[]']").map(function () {
                return String($(this).attr('data-idunidad'));
            }).get();
        }

        // ── Helper: la unidad destino no puede ser ninguna unidad de origen ──
        // (ni la seleccionada ahora, ni las que ya están en el detalle)
        function sincronizarDestino() {
            var origen = $('#select-unidad').val();

            $('#select-otra-unidad option').each(function () {
                var v = this.value;
                $(this).prop('disabled', v === '0' || (unidadEspecifica(origen) && v === String(origen)));
            });

            var destino = $('#select-otra-unidad').val();
            if (unidadEspecifica(origen) && destino && destino !== '0' && String(destino) === String(origen)) {
                $('#select-otra-unidad').val('0');
                toastr.info('Se quitó la Unidad Destino porque coincide con la Unidad de Origen');
            }

            $('#select-otra-unidad').trigger('change.select2');
        }

        // ── Helper: cargar unidades que tienen items en el contrato ──────────
        function cargarUnidades(idContrato, habilitar) {
            var placeholder = '<option value="0" selected disabled>Seleccionar Unidad</option>';

            $('#select-unidad').html(placeholder).val('0').trigger('change');

            if (!habilitar) return;

            axios.post(urlAdmin + '/admin/contrato/unidades', { id_contrato: idContrato })
                .then((response) => {
                    var opciones = '';
                    $.each(response.data, function (i, u) {
                        opciones += '<option value="' + u.id + '">' + esc(u.nombre) + '</option>';
                    });

                    if (opciones === '') {
                        toastr.info('Este contrato no tiene materiales asignados a unidades');
                        return;
                    }

                    // Primera opción "Todos", queda seleccionada por defecto
                    $('#select-unidad')
                        .html(placeholder + '<option value="todos">Todos</option>' + opciones)
                        .val('todos')
                        .trigger('change');
                })
                .catch(() => { toastr.error('Error al cargar las unidades'); });
        }

        // ── Helper: vaciar tabla de detalle (solo al cambiar de contrato) ──────
        function limpiarDetalle() {
            if ($('#matriz > tbody > tr').length > 0) {
                toastr.info('Se limpió el detalle porque cambió el contrato');
            }
            $('#matriz tbody tr').remove();
            actualizarContador();
        }

        // ── Helper: leer campos del encabezado ───────────────────────────
        function getCamposEncabezado() {
            return {
                fecha:         document.getElementById('fecha').value,
                contrato:      document.getElementById('select-contrato').value,
                otraUnidad:    document.getElementById('check-otra-unidad').checked,
                unidadDestino: document.getElementById('select-otra-unidad').value,
                descripcion:   document.getElementById('descripcion').value.trim(),
                noFactura:     document.getElementById('no_factura').value.trim(),
                fechaFactura:  document.getElementById('fecha_factura').value,
            };
        }

        // ── Ver información del contrato (todas las unidades o una en específico) ──
        function verInfoContrato() {
            var idContrato = $('#select-contrato').val();
            if (!idContrato || idContrato === '0') { toastr.error('Seleccione un contrato'); return; }

            var unidadSel = $('#select-unidad').val();
            var filtro    = unidadEspecifica(unidadSel) ? unidadSel : null;

            openLoading();
            axios.post(urlAdmin + '/admin/contrato/info', {
                id_contrato: idContrato,
                id_departamento: filtro
            })
                .then((response) => {
                    closeLoading();
                    var d = response.data;

                    if (d.success !== 1) { toastr.error('No se pudo cargar el contrato'); return; }

                    $('#info-codigo').text(d.codigo ?? '-');
                    $('#info-proveedor').text(d.proveedor ?? '-');
                    $('#info-nombre').text(d.nombre_proceso ?? '-');
                    $('#info-fecha-inicio').text(d.fecha_inicio ?? '-');
                    $('#info-fecha-fin').text(d.fecha_fin ?? '-');
                    $('#info-descripcion').text(d.descripcion ?? 'Sin descripción');

                    // Encabezados y etiqueta según el filtro
                    if (filtro) {
                        $('#info-filtro').text('Unidad: ' + (d.unidad_nombre ?? '-'));
                        $('#th-contratado').text('Asignado a la unidad');
                        $('#th-retirado').text('Retirado por la unidad');
                        $('#th-disponible').text('Disponible en la unidad');
                    } else {
                        $('#info-filtro').text('Todas las unidades');
                        $('#th-contratado').text('Contratado');
                        $('#th-retirado').text('Retirado');
                        $('#th-disponible').text('Disponible');
                    }

                    var filas = '';
                    $.each(d.detalle, function (i, item) {
                        var claseFila = item.disponible <= 0 ? ' class="fila-agotada"' : '';

                        var unidades = '';
                        $.each(item.unidades, function (j, u) {
                            var esSel = String(u.id) === String(filtro);
                            unidades += '<span class="badge badge-unidad ' + (esSel ? 'badge-primary' : 'badge-secondary') + '">' +
                                esc(u.nombre) +
                                '</span><br>';
                        });

                        filas += '<tr' + claseFila + '>' +
                            '<td>' + esc(item.nombre) + '</td>' +
                            '<td>' + esc(item.unidad_medida ?? '-') + '</td>' +
                            '<td>' + esc(item.cantidad) + '</td>' +
                            '<td>' + esc(item.retirado) + '</td>' +
                            '<td>' + esc(item.disponible) + '</td>' +
                            '<td>' + esc(item.precio) + '</td>' +
                            '<td>' + (unidades || '<span class="text-muted">Sin asignar</span>') + '</td>' +
                            '</tr>';
                    });

                    if (!d.detalle || d.detalle.length === 0) {
                        var msg = filtro
                            ? 'Esta unidad no tiene materiales asignados en este contrato'
                            : 'Este contrato no tiene materiales registrados';
                        filas = '<tr><td colspan="7" class="text-center">' + msg + '</td></tr>';
                    }

                    $('#tabla-detalle-contrato').html(filas);
                    $('#modalInfoContrato').modal('show');
                })
                .catch(() => { toastr.error('Error al cargar información'); closeLoading(); });
        }

        // ── Abrir modal buscador ──────────────────────────────────────────
        function abrirModal() {
            var unidad = $('#select-unidad').val();
            if (!unidadEspecifica(unidad)) { toastr.error('Seleccione una unidad de origen específica'); return; }

            document.getElementById('tablaRepuesto').innerHTML = "";
            document.getElementById("formulario-repuesto").reset();
            $('.droplista').hide().html('');
            window.materialesBusqueda = {};
            $('#modalRepuesto').modal('show');
        }

        // ── Validar teclas numéricas ──────────────────────────────────────
        function validateInput(event) {
            const key = event.key;
            if (["Backspace","ArrowLeft","ArrowRight","Delete","Tab"].includes(key)) return true;
            if (key === "e" || key === "E" || key === "-" || isNaN(Number(key))) return false;
            return true;
        }

        // ── Teclas del input de cantidad: Enter agrega al detalle ─────────
        function teclaCantidad(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                agregarAlDetalle();
                return false;
            }
            return validateInput(event);
        }

        // ── Buscar material (con espera de 250 ms entre teclas) ───────────
        function buscarMaterial(e) {
            clearTimeout(window.buscadorTimer);
            window.buscadorTimer = setTimeout(function () { ejecutarBusqueda(e); }, 250);
        }

        function ejecutarBusqueda(e) {
            var seq        = ++window.buscadorSeq;
            var row        = $(e).closest('tr');
            var idContrato = $('#select-contrato').val();
            var idUnidad   = $('#select-unidad').val();

            if (!unidadEspecifica(idUnidad)) return;

            axios.post(urlAdmin + '/admin/contrato/buscar/material', {
                'query': e.value,
                'id_contrato': idContrato,
                'id_departamento': idUnidad
            })
                .then((response) => {
                    // Si ya hay una búsqueda más nueva, se ignora esta respuesta
                    if (seq !== window.buscadorSeq) return;

                    window.materialesBusqueda = {};

                    var html = '<div class="list-group">';
                    if (!response.data || response.data.length === 0) {
                        html += '<div class="list-group-item">Sin resultados</div>';
                    } else {
                        $.each(response.data, function (i, item) {
                            window.materialesBusqueda[item.id] = item;

                            var agotado = Number(item.disponible) <= 0;

                            html += '<a href="#" class="list-group-item list-group-item-action' + (agotado ? ' disabled' : '') + '" ' +
                                'onclick="seleccionarMaterial(' + Number(item.id) + '); return false;">' +
                                esc(item.nombre) + ' <small>(' + esc(item.unidad) + ')</small>' +
                                '<span class="float-right badge ' + (agotado ? 'badge-danger' : 'badge-success') + '">' +
                                (agotado ? 'Agotado' : 'Disponible: ' + esc(item.disponible)) +
                                '</span>' +
                                '</a>';
                        });
                    }
                    html += '</div>';

                    $(row).each(function () {
                        $(this).find(".droplista").fadeIn();
                        $(this).find(".droplista").html(html);
                    });
                })
                .catch(() => { /* sin acción: el siguiente intento vuelve a buscar */ });
        }

        // ── Click en un resultado del buscador ────────────────────────────
        function seleccionarMaterial(id) {
            var item = window.materialesBusqueda[id];
            if (item) modificarValor(item);
        }

        // ── Seleccionar material → abrir modal cantidad ───────────────────
        function modificarValor(item) {
            if (item.disponible <= 0) {
                toastr.info('NO HAY DISPONIBILIDAD PARA ESTE MATERIAL EN LA UNIDAD');
                return;
            }

            var idUnidad     = $('#select-unidad').val();
            var nombreUnidad = $('#select-unidad option:selected').text();

            // Mismo material + misma unidad = repetido. Misma unidad distinta sí se permite
            if ($("input[data-clave='" + Number(item.id) + "-" + Number(idUnidad) + "']").length > 0) {
                toastr.error('Este material ya fue agregado al detalle para esta unidad');
                $('.droplista').hide();
                return;
            }

            $('#id-material-seleccionado').val(item.id);
            $('#id-unidad-seleccionada').val(idUnidad);
            $('#precio-num-seleccionado').val(item.precio_num);
            $('#info-material').val(item.nombre);
            $('#info-unidad-origen').val(nombreUnidad);
            $('#info-medida').val(item.precio);
            $('#info-unidad').val(item.unidad);

            var disp = Number(item.disponible);

            var markup = "<tr>" +
                "<td class='text-center'>" + Number(item.asignado) + "</td>" +
                "<td class='text-center'>" + Number(item.retirado_unidad) + "</td>" +
                "<td class='text-center'><b style='color:#28a745'>" + disp + "</b></td>" +
                "<td>" +
                "<div class='input-group input-group-sm'>" +
                "<input class='form-control' id='input-cantidad-retiro' min='1' max='" + disp + "' type='number' " +
                "onkeydown=\"return teclaCantidad(event);\" " +
                "oninput=\"validateCantidadSalida(this, " + disp + ");\">" +
                "<div class='input-group-append'>" +
                "<button type='button' class='btn btn-outline-secondary' onclick='retirarTodo(" + disp + ")'>Todo</button>" +
                "</div>" +
                "</div>" +
                "</td>" +
                "</tr>";

            $("#matrizM tbody").html(markup);
            $('#modalCantidad').modal('show');
        }

        // ── Botón "Todo": llena con el disponible ─────────────────────────
        function retirarTodo(disponible) {
            $('#input-cantidad-retiro').val(disponible).trigger('focus');
        }

        // ── Agregar fila al detalle ────────────────────────────────────────
        function agregarAlDetalle() {
            var idDetalle    = $('#id-material-seleccionado').val();
            var idUnidad     = $('#id-unidad-seleccionada').val();
            var nombreUnidad = $('#info-unidad-origen').val();
            var nombre       = $('#info-material').val();
            var unidad       = $('#info-unidad').val();
            var precioTexto  = $('#info-medida').val();
            var precioNum    = Number($('#precio-num-seleccionado').val()) || 0;
            var cantidad     = $('#input-cantidad-retiro').val();
            var disponible   = $('#input-cantidad-retiro').attr('max');

            if (cantidad === '' || cantidad === undefined) { toastr.error('Ingrese una cantidad'); return; }
            if (Number(cantidad) <= 0) { toastr.error('No se permite cero'); return; }
            if (Number(cantidad) > Number(disponible)) { toastr.error('Supera la cantidad disponible'); return; }

            if ($("input[data-clave='" + Number(idDetalle) + "-" + Number(idUnidad) + "']").length > 0) {
                toastr.error('Este material ya fue agregado al detalle para esta unidad');
                return;
            }

            colorBlancoTabla();

            var nFilas   = $('#matriz > tbody > tr').length + 1;
            var subtotal = Math.round(precioNum * Number(cantidad) * 10000) / 10000;
            var subtotalTexto = '$' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            var markup = "<tr>" +
                "<td><p id='fila" + nFilas + "' class='form-control' style='max-width:55px'>" + nFilas + "</p></td>" +
                "<td>" +
                "<input name='idmaterialArray[]' type='hidden' data-idcontratodetalle='" + Number(idDetalle) + "' data-idunidad='" + Number(idUnidad) + "' data-clave='" + Number(idDetalle) + "-" + Number(idUnidad) + "'>" +
                "<input disabled value='" + esc(nombre) + "' title='" + esc(nombre) + "' class='form-control form-control-sm' type='text'>" +
                "</td>" +
                "<td><input disabled value='" + esc(unidad) + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><input disabled value='" + esc(nombreUnidad) + "' title='" + esc(nombreUnidad) + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><input disabled value='" + esc(precioTexto) + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><input name='salidaArray[]' disabled data-cantidadSalida='" + Number(cantidad) + "' data-subtotal='" + subtotal + "' value='" + Number(cantidad) + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><input disabled value='" + esc(subtotalTexto) + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><button type='button' class='btn btn-danger btn-block btn-sm' onclick='borrarFila(this)'>Borrar</button></td>" +
                "</tr>";

            $("#matriz tbody").append(markup);

            actualizarContador();
            sincronizarDestino();
            $('#modalCantidad').modal('hide');
            document.getElementById('inputBuscador').value = '';
            $('.droplista').hide().html('');

            toastr.success("Agregado");
        }

        // ── Preguntar antes de guardar ────────────────────────────────────
        function preguntaGuardar() {
            if (window.guardando) return;

            colorBlancoTabla();
            Swal.fire({
                title: '¿Guardar Retiro?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, guardar'
            }).then((result) => { if (result.isConfirmed) guardarSalida(); });
        }

        // ── Guardar retiro ────────────────────────────────────────────────
        // ── Guardar retiro ────────────────────────────────────────────────
        function guardarSalida() {
            if (window.guardando) return;

            var c = getCamposEncabezado();

            if (!c.fecha)                          { toastr.error('Fecha es requerida');     return; }
            if (!c.contrato || c.contrato === '0') { toastr.error('Seleccione un Contrato'); return; }

            if ($('#matriz > tbody > tr').length <= 0) {
                toastr.error('Debe agregar al menos un ítem de retiro');
                return;
            }

            var reglaEntero       = /^[0-9]\d*$/;
            var idContratoDetalle = $("input[name='idmaterialArray[]']").map(function () { return $(this).attr("data-idcontratodetalle"); }).get();
            var idUnidadOrigen    = $("input[name='idmaterialArray[]']").map(function () { return $(this).attr("data-idunidad"); }).get();
            var cantidadRetiro    = $("input[name='salidaArray[]']").map(function ()     { return $(this).attr("data-cantidadSalida"); }).get();

            if (c.otraUnidad) {
                if (!c.unidadDestino || c.unidadDestino === '0') {
                    toastr.error('Seleccione la Unidad Destino');
                    return;
                }

                // Ninguna línea puede salir de la misma unidad a la que va el material
                for (var k = 0; k < idUnidadOrigen.length; k++) {
                    if (String(idUnidadOrigen[k]) === String(c.unidadDestino)) {
                        colorRojoTabla(k);
                        toastr.error('Fila #' + (k + 1) + ' — La Unidad de Origen es igual a la Unidad Destino');
                        return;
                    }
                }

                if (c.descripcion === '') {
                    toastr.error('Indique en la descripción el motivo del retiro a otra unidad');
                    return;
                }
            }

            for (var a = 0; a < idContratoDetalle.length; a++) {
                var ic = cantidadRetiro[a];
                if (!ic)                    { colorRojoTabla(a); toastr.error('Fila #' + (a+1) + ' — Cantidad requerida');           return; }
                if (!ic.match(reglaEntero)) { colorRojoTabla(a); toastr.error('Fila #' + (a+1) + ' — Debe ser entero positivo');     return; }
                if (ic <= 0)                { colorRojoTabla(a); toastr.error('Fila #' + (a+1) + ' — No puede ser cero o negativo'); return; }
                if (ic > 1000000)           { colorRojoTabla(a); toastr.error('Fila #' + (a+1) + ' — Máximo 1,000,000');             return; }
            }

            var contenedorArray = [];
            for (var p = 0; p < idContratoDetalle.length; p++) {
                contenedorArray.push({
                    id_contrato_detalle: idContratoDetalle[p],
                    id_departamento:     idUnidadOrigen[p],   // unidad de origen de esta línea
                    cantidad:            cantidadRetiro[p],
                });
            }

            window.guardando = true;
            $('#botonGuardar').prop('disabled', true);

            openLoading();
            var formData = new FormData();
            formData.append('id_contrato',              c.contrato);
            formData.append('otra_unidad',              c.otraUnidad ? 1 : 0);
            formData.append('id_departamento_destino',  c.otraUnidad ? c.unidadDestino : '');
            formData.append('fecha',                    c.fecha);
            formData.append('no_factura',               c.noFactura);
            formData.append('fecha_factura',            c.fechaFactura);
            formData.append('descripcion',              c.descripcion);
            formData.append('contenedorArray',          JSON.stringify(contenedorArray));

            axios.post(urlAdmin + '/admin/contrato/retiro/guardar', formData)
                .then((response) => {

                    closeLoading();
                    window.guardando = false;
                    $('#botonGuardar').prop('disabled', false);

                    if      (response.data.success === 1)  { toastr.error('Se requiere al menos un ítem de retiro'); }
                    else if (response.data.success === 2) {
                        Swal.fire({
                            title: 'Cantidad no disponible',
                            html:
                                '<b>' + esc(response.data.nombre_material) + '</b><br><br>' +
                                (response.data.unidad ? 'Unidad: <b>' + esc(response.data.unidad) + '</b><br>' : '') +
                                'Solicitado: <b>' + esc(response.data.cantidad_pedida) + '</b><br>' +
                                'Disponible en la unidad: <b>' + esc(response.data.disponible) + '</b>',
                            icon: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
                    }
                    else if (response.data.success === 3) { toastr.error("El contrato ya no está vigente"); }
                    else if (response.data.success === 4) { toastr.error("Indique en la descripción el motivo del retiro a otra unidad"); }
                    else if (response.data.success === 5) { toastr.error("Contrato no encontrado"); }
                    else if (response.data.success === 6) { toastr.error("Material no pertenece al contrato seleccionado"); }
                    else if (response.data.success === 7) { toastr.error("Hay materiales que no están asignados a su unidad de origen"); }
                    else if (response.data.success === 8) { toastr.error("Unidad destino no válida (una línea tiene la misma unidad de origen)"); }
                    else if (response.data.success === 9) { toastr.error("Hay materiales repetidos para la misma unidad"); }
                    else if (response.data.success === 10) { msgActualizado(); }
                    else                                   { toastr.error('Error al guardar'); }
                })
                .catch(() => {
                    toastr.error('Error al guardar');
                    closeLoading();
                    window.guardando = false;
                    $('#botonGuardar').prop('disabled', false);
                });
        }

        // ── Mensaje final ─────────────────────────────────────────────────
        function msgActualizado() {
            Swal.fire({
                title: 'Retiro Registrado',
                icon: 'success',
                allowOutsideClick: false,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (result.isConfirmed) location.reload();
            });
        }

        // ── Utilidades tabla ──────────────────────────────────────────────
        function borrarFila(elemento) {
            elemento.closest('tr').remove();
            setearFila();
            actualizarContador();
            sincronizarDestino();
        }

        // Renumera solo las filas del cuerpo (el pie de tabla tiene los totales)
        function setearFila() {
            $('#matriz tbody tr').each(function (i) {
                $(this).find('td:first p').text(i + 1);
            });
        }

        function actualizarContador() {
            var n = $('#matriz > tbody > tr').length;
            $('#contador-filas').text(n + (n === 1 ? ' ítem' : ' ítems'));

            var totalCantidad = 0, totalSubtotal = 0;
            $("#matriz input[name='salidaArray[]']").each(function () {
                totalCantidad += Number($(this).attr('data-cantidadSalida')) || 0;
                totalSubtotal += Number($(this).attr('data-subtotal')) || 0;
            });

            $('#total-cantidad').text(totalCantidad.toLocaleString('en-US'));
            $('#total-subtotal').text('$' + totalSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        function colorRojoTabla(index) {
            $("#matriz tbody tr").eq(index).css('background', '#f8d7da');
        }

        function colorBlancoTabla() {
            $("#matriz tbody tr").css('background', 'white');
        }

        function validateCantidadSalida(input, maxCantidad) {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (Number(input.value) > maxCantidad) input.value = maxCantidad;
        }

    </script>

@endsection
