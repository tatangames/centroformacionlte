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

                        {{-- Fila 2: Contrato + Ver Información --}}
                        <div class="row">
                            <div class="col-md-9">
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
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" id="botonInfoContrato" onclick="verInfoContrato()"
                                        class="btn btn-info btn-block" disabled
                                        style="border-radius:6px; margin-bottom:16px; font-weight:400">
                                    <i class="fas fa-info-circle mr-1"></i> Ver Información
                                </button>
                            </div>
                        </div>

                        <hr class="divider-azul">

                        {{-- Fila 3: Descripción + Botón --}}
                        <div class="row align-items-end">
                            <div class="col-md-8 mb-2">
                                <label class="field-label">
                                    <i class="fas fa-align-left mr-1"></i>Descripción
                                    <small style="text-transform:none; font-weight:400">(Opcional)</small>
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
                                   style="table-layout:fixed; width:100%">
                                <thead>
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:35%">Material</th>
                                    <th style="width:15%">Cantidad</th>
                                    <th style="width:10%">Opciones</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mt-3" style="margin: 10px; padding-bottom: 15px">
                        <button type="button"
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
                        <form id="formulario-repuesto">
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="field-label">
                                        Material — Regresa: Nombre / Disponible
                                        <span class="badge badge-success ml-1">Solo con inventario del mismo contrato</span>
                                    </label>
                                    <table class="table" id="matriz-busqueda">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input id="inputBuscador" autocomplete="off"
                                                       class="form-control" style="width:100%"
                                                       onkeyup="buscarMaterial(this)"
                                                       maxlength="300" type="text"
                                                       placeholder="Escribir nombre del material…">
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
                        <form id="formulario-material">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">

                                        <input type="hidden" id="id-material-seleccionado">

                                        <div class="form-row mb-3">
                                            <div class="col-md-6">
                                                <label class="field-label">Material</label>
                                                <input type="text" disabled class="form-control" id="info-material">
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
                                                    <th>Disponible</th>
                                                    <th>Cantidad a Retirar</th>
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
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Unidad de Medida</th>
                                    <th>Contratado</th>
                                    <th>Retirado</th>
                                    <th>Disponible</th>
                                    <th>Precio $</th>
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

            window.seguroBuscador = true;

            $(document).click(function () { $(".droplista").hide(); });

            $('#select-contrato').select2({
                theme: "bootstrap-5",
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });

            $('#select-contrato').on('change', function () {
                var val = $(this).val();
                var habilitar = !!val && val !== '0';

                $('#botonaddmaterial').prop('disabled', !habilitar);
                $('#botonInfoContrato').prop('disabled', !habilitar);

                // Limpiar tabla y contador al cambiar de contrato
                $('#matriz tbody tr').remove();
                actualizarContador();

                $('#select-contrato').select2('close');
            });
        });
    </script>

    <script>

        // ── Helper: leer campos del encabezado ───────────────────────────
        function getCamposEncabezado() {
            return {
                fecha:        document.getElementById('fecha').value,
                contrato:     document.getElementById('select-contrato').value,
                descripcion:  document.getElementById('descripcion').value,
                noFactura:    document.getElementById('no_factura').value,
                fechaFactura: document.getElementById('fecha_factura').value,
            };
        }

        // ── Ver información del contrato ─────────────────────────────────
        function verInfoContrato() {
            var idContrato = $('#select-contrato').val();
            if (!idContrato || idContrato === '0') { toastr.error('Seleccione un contrato'); return; }

            openLoading();
            axios.post(urlAdmin + '/admin/contrato/info', { id_contrato: idContrato })
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

                    var filas = '';
                    $.each(d.detalle, function (i, item) {
                        var claseFila = item.disponible <= 0 ? ' class="fila-agotada"' : '';
                        filas += '<tr' + claseFila + '>' +
                            '<td>' + item.nombre + '</td>' +
                            '<td>' + (item.unidad_medida ?? '-') + '</td>' +
                            '<td>' + item.cantidad + '</td>' +
                            '<td>' + item.retirado + '</td>' +
                            '<td>' + item.disponible + '</td>' +
                            '<td>' + item.precio + '</td>' +
                            '</tr>';
                    });

                    if (!d.detalle || d.detalle.length === 0) {
                        filas = '<tr><td colspan="6" class="text-center">Este contrato no tiene materiales registrados</td></tr>';
                    }

                    $('#tabla-detalle-contrato').html(filas);
                    $('#modalInfoContrato').modal('show');
                })
                .catch(() => { toastr.error('Error al cargar información'); closeLoading(); });
        }

        // ── Abrir modal buscador ──────────────────────────────────────────
        function abrirModal() {
            document.getElementById('tablaRepuesto').innerHTML = "";
            document.getElementById("formulario-repuesto").reset();
            $('#modalRepuesto').modal('show');
        }

        // ── Validar teclas numéricas ──────────────────────────────────────
        function validateInput(event) {
            const key = event.key;
            if (["Backspace","ArrowLeft","ArrowRight","Delete","Tab"].includes(key)) return true;
            if (key === "e" || key === "E" || key === "-" || isNaN(Number(key))) return false;
            return true;
        }

        // ── Buscar material (buscador con dropdown) ───────────────────────
        function buscarMaterial(e) {
            if (seguroBuscador) {
                seguroBuscador = false;
                var row        = $(e).closest('tr');
                var texto      = e.value;
                var idContrato = $('#select-contrato').val();

                axios.post(urlAdmin + '/admin/contrato/buscar/material', {
                    'query': texto,
                    'id_contrato': idContrato
                })
                    .then((response) => {
                        seguroBuscador = true;

                        var html = '<div class="list-group">';
                        if (!response.data || response.data.length === 0) {
                            html += '<div class="list-group-item">Sin resultados</div>';
                        } else {
                            $.each(response.data, function (i, item) {
                                html += '<a href="#" class="list-group-item list-group-item-action" ' +
                                    "onclick='modificarValor(" + JSON.stringify(item) + "); return false;'>" +
                                    item.nombre + ' — Disponible: ' + item.disponible
                                    '</a>';
                            });
                        }
                        html += '</div>';

                        $(row).each(function () {
                            $(this).find(".droplista").fadeIn();
                            $(this).find(".droplista").html(html);
                        });
                    })
                    .catch(() => { seguroBuscador = true; });
            }
        }

        // ── Seleccionar material → abrir modal cantidad ───────────────────
        // ── Seleccionar material → abrir modal cantidad ───────────────────
        function modificarValor(item) {
            if (item.disponible <= 0) {
                toastr.info('NO HAY DISPONIBILIDAD PARA ESTE MATERIAL');
                return;
            }

            // NUEVO: validar duplicado ANTES de abrir el modal de cantidad
            if ($("input[data-idcontratodetalle='" + item.id + "']").length > 0) {
                toastr.error('Este material ya fue agregado al detalle');
                $('.droplista').hide();
                return;
            }

            $('#id-material-seleccionado').val(item.id);
            $('#info-material').val(item.nombre);
            $('#info-medida').val(item.precio);

            var markup = "<tr>" +
                "<td><input disabled value='" + item.disponible + "' data-disponibleFila='" + item.disponible + "' class='form-control form-control-sm' type='text'></td>" +
                "<td>" +
                "<input class='form-control form-control-sm' id='input-cantidad-retiro' " +
                "data-idcontratodetallefila='" + item.id + "' min='1' max='" + item.disponible + "' type='number' " +
                "onkeydown=\"return validateInput(event);\" " +
                "oninput=\"validateCantidadSalida(this, " + item.disponible + ");\">" +
                "</td>" +
                "</tr>";

            $("#matrizM tbody").html(markup);
            $('#modalCantidad').modal('show');
        }

        // ── Agregar fila al detalle ────────────────────────────────────────
        function agregarAlDetalle() {
            var idDetalle = $('#id-material-seleccionado').val();
            var nombre    = $('#info-material').val();
            var cantidad  = $('#input-cantidad-retiro').val();
            var disponible = $('#input-cantidad-retiro').attr('max');

            if (cantidad === '' || cantidad === undefined) { toastr.error('Ingrese una cantidad'); return; }
            if (Number(cantidad) <= 0) { toastr.error('No se permite cero'); return; }
            if (Number(cantidad) > Number(disponible)) { toastr.error('Supera la cantidad disponible'); return; }

            if ($("input[data-idcontratodetalle='" + idDetalle + "']").length > 0) {
                toastr.error('Este material ya fue agregado al detalle');
                return;
            }

            colorBlancoTabla();

            var nFilas = $('#matriz > tbody > tr').length + 1;

            var markup = "<tr>" +
                "<td><p id='fila" + nFilas + "' class='form-control' style='max-width:55px'>" + nFilas + "</p></td>" +
                "<td>" +
                "<input name='idmaterialArray[]' type='hidden' data-idcontratodetalle='" + idDetalle + "'>" +
                "<input disabled value='" + nombre + "' class='form-control form-control-sm' type='text'>" +
                "</td>" +
                "<td><input name='salidaArray[]' disabled data-cantidadSalida='" + cantidad + "' value='" + cantidad + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><button type='button' class='btn btn-danger btn-block btn-sm' onclick='borrarFila(this)'>Borrar</button></td>" +
                "</tr>";

            $("#matriz tbody").append(markup);

            actualizarContador();
            $('#modalCantidad').modal('hide');
            document.getElementById('inputBuscador').value = '';

            toastr.success("Agregado");
        }

        // ── Preguntar antes de guardar ────────────────────────────────────
        function preguntaGuardar() {
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
        function guardarSalida() {
            var c = getCamposEncabezado();

            if (!c.fecha)                          { toastr.error('Fecha es requerida');     return; }
            if (!c.contrato || c.contrato === '0') { toastr.error('Seleccione un Contrato'); return; }

            if ($('#matriz > tbody > tr').length <= 0) {
                toastr.error('Debe agregar al menos un ítem de retiro');
                return;
            }

            var reglaEntero      = /^[0-9]\d*$/;
            var idContratoDetalle = $("input[name='idmaterialArray[]']").map(function () { return $(this).attr("data-idcontratodetalle"); }).get();
            var cantidadRetiro    = $("input[name='salidaArray[]']").map(function ()     { return $(this).attr("data-cantidadSalida"); }).get();

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
                    cantidad:            cantidadRetiro[p],
                });
            }

            openLoading();
            var formData = new FormData();
            formData.append('id_contrato',      c.contrato);
            formData.append('fecha',            c.fecha);
            formData.append('no_factura',       c.noFactura);
            formData.append('fecha_factura',    c.fechaFactura);
            formData.append('descripcion',      c.descripcion);
            formData.append('contenedorArray',  JSON.stringify(contenedorArray));

            axios.post(urlAdmin + '/admin/contrato/retiro/guardar', formData)
                .then((response) => {

                    console.log(response);

                    closeLoading();
                    if      (response.data.success === 1)  { toastr.error('Se requiere al menos un ítem de retiro'); }
                    else if (response.data.success === 2) {
                        Swal.fire({
                            title: 'Cantidad no disponible',
                            html:
                                '<b>' + response.data.nombre_material + '</b><br><br>' +
                                'Solicitado: <b>' + response.data.cantidad_pedida + '</b><br>' +
                                'Disponible: <b>' + response.data.disponible + '</b>',
                            type: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
                    }
                    else if (response.data.success === 3) { toastr.error("El contrato ya no está vigente"); }
                    else if (response.data.success === 5) { toastr.error("Contrato no encontrado"); }
                    else if (response.data.success === 6) { toastr.error("Material no pertenece al contrato seleccionado"); }
                    else if (response.data.success === 10) { msgActualizado(); }
                    else                                   { toastr.error('Error al guardar'); }
                })
                .catch(() => { toastr.error('Error al guardar'); closeLoading(); });
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
        }

        function setearFila() {
            var table  = document.getElementById('matriz');
            var conteo = 0;
            for (var r = 1, n = table.rows.length; r < n; r++) {
                conteo++;
                var el = table.rows[r].cells[0].children[0];
                el.innerHTML = conteo;
            }
        }

        function actualizarContador() {
            var n = $('#matriz > tbody > tr').length;
            $('#contador-filas').text(n + (n === 1 ? ' ítem' : ' ítems'));
        }

        function colorRojoTabla(index) {
            $("#matriz tr:eq(" + (index + 1) + ")").css('background', '#f8d7da');
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
