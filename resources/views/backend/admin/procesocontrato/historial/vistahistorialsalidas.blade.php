@extends('adminlte::page')

@section('title', 'Historial / Salidas de Contratos')

@section('content_header')
    <h1>Historial / Salidas de Contratos</h1>
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">

    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">{{ Auth::guard('admin')->user()->nombre }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <a href="{{ route('admin.perfil') }}" class="dropdown-item">
                <i class="fas fa-user mr-2"></i> Editar Perfil
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
    <div id="divcontenedor">

        {{-- ══ FILTROS ══ --}}
        <section class="content" style="margin-bottom:0">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
                    </div>
                    <div class="card-body">

                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label class="font-weight-bold">Contrato</label>
                                <select class="form-control" id="filtro-contrato">
                                    <option value="">— Todos —</option>
                                    @foreach($arrayContratos as $c)
                                        <option value="{{ $c->id }}"
                                                data-cerrado="{{ $c->estado == 'finalizado' ? '1' : '0' }}">
                                            {{ $c->codigo ? $c->codigo . ' — ' : '' }}{{ $c->nombre_proceso }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold">Fecha Registro Desde</label>
                                <input type="date" class="form-control" id="filtro-fecha-desde">
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold">Fecha Registro Hasta</label>
                                <input type="date" class="form-control" id="filtro-fecha-hasta">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div style="width:100%">
                                    <button class="btn btn-primary btn-block mb-1" onclick="buscarConFiltros()">
                                        <i class="fas fa-search mr-1"></i> Filtrar
                                    </button>
                                    <button class="btn btn-secondary btn-block" onclick="limpiarFiltros()">
                                        <i class="fas fa-times mr-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-end mt-3">
                            <div class="col-md-5">
                                <label class="font-weight-bold">
                                    <i class="fas fa-box mr-1 text-muted"></i> Buscar por ítem del contrato (nombre)
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="filtro-material"
                                       placeholder="Ej: cemento, servicio de transporte ...">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold">
                                    <i class="fas fa-building mr-1 text-muted"></i> Unidad de Origen
                                </label>
                                <select class="form-control" id="filtro-unidad">
                                    <option value="">— Todas —</option>
                                    @foreach($arrayDepartamentos as $dep)
                                        <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <small class="text-muted">
                                    Filtra los retiros que tengan ese ítem o esa unidad de origen en su detalle.
                                </small>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        {{-- ══ TABLA ══ --}}
        <section class="content">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Retiros</h3>
                        <div class="card-tools">
                            <span class="badge badge-info" id="badge-total" style="display:none"></span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="div-instruccion" class="text-center text-muted py-5">
                            <i class="fas fa-search fa-3x mb-3 d-block"></i>
                            <p class="mb-0">Utiliza los filtros de arriba y presiona <strong>Filtrar</strong> para ver el historial.</p>
                        </div>
                        <div id="div-tabla" style="display:none">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="tablaDatatable"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Modal Editar Retiro --}}
    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Editar Retiro
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-editar" onsubmit="return false;">
                        <input type="hidden" id="id-editar">
                        <div class="form-group">
                            <label>Fecha <span class="text-danger">*</span></label>
                            <input type="date" id="fecha-editar" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>No. Factura</label>
                            <input type="text" id="no-factura-editar" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Fecha Factura</label>
                            <input type="date" id="fecha-factura-editar" class="form-control">
                        </div>

                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="check-otra-editar"
                                   onchange="toggleDestinoEditar()">
                            <label class="custom-control-label" for="check-otra-editar" style="font-weight:600">
                                Es para otra unidad
                            </label>
                        </div>

                        <div class="form-group" id="contenedor-destino-editar" style="display:none">
                            <label>Unidad Destino <span class="text-danger">*</span></label>
                            <select id="destino-editar" class="form-control">
                                <option value="">Seleccionar Unidad Destino</option>
                                @foreach($arrayDepartamentos as $dep)
                                    <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                No se pueden elegir las unidades de origen de este retiro.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>
                                Descripción
                                <small id="texto-descripcion-editar" class="text-muted">(Opcional)</small>
                            </label>
                            <textarea id="descripcion-editar" class="form-control"
                                      rows="3" maxlength="800"
                                      placeholder="Descripción opcional"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="editar()">
                        <i class="fas fa-save mr-1"></i>Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detalle Retiro --}}
    <div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-list mr-2"></i>
                        Detalle de Retiro —
                        <span id="detalle-contrato"></span>
                        <small class="ml-2" id="detalle-fecha"></small>
                        <span id="detalle-badge-cerrado" class="badge badge-danger ml-2" style="display:none;">
                            Contrato Finalizado
                        </span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="detalle-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div id="detalle-contenido" style="display:none;">
                        <div id="detalle-destino" class="alert alert-warning py-2 px-3 mb-3" style="display:none;">
                            <i class="fas fa-share mr-1"></i>
                            Retiro para otra unidad. <b>Destino:</b> <span id="detalle-destino-nombre"></span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead class="thead-dark">
                                <tr>
                                    <th style="width:4%">#</th>
                                    <th>Ítem</th>
                                    <th class="text-center" style="width:8%">U/M</th>
                                    <th class="text-center" style="width:18%">Unidad de Origen</th>
                                    <th class="text-center" style="width:9%">Cantidad</th>
                                    <th class="text-right" style="width:11%">Precio unitario</th>
                                    <th class="text-right" style="width:11%">Subtotal</th>
                                    <th class="text-center" style="width:110px">Opciones</th>
                                </tr>
                                </thead>
                                <tbody id="detalle-tbody"></tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="6" class="text-right">Total</th>
                                    <th class="text-right" id="detalle-total">$0.00</th>
                                    <th></th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div id="detalle-vacio" class="text-center text-muted py-4" style="display:none;">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Este retiro no tiene ítems registrados.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Editar Ítem del Detalle --}}
    <div class="modal fade" id="modalEditarItem" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Editar Cantidad
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id-item-editar">

                    <p class="mb-1"><b>Ítem:</b> <span id="item-editar-material"></span></p>
                    <p class="mb-3"><b>Unidad de Origen:</b> <span id="item-editar-origen"></span></p>

                    <div class="form-group mb-0">
                        <label>Cantidad</label>
                        <input type="number" min="1" class="form-control" id="cantidad-item-editar"
                               onkeydown="return validateInputItem(event);"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="guardarEdicionItem()">
                        <i class="fas fa-save mr-1"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>
        const RUTA_TABLA = "{{ url('/admin/historial/contratos/salidas/tabla') }}";

        window.idRetiroActual = null;
        window.detalleCtx     = { contrato: '', fecha: '', cerrado: false };
        window.origenesRetiro = [];

        // ── Helper: escapar texto para insertarlo en HTML ──
        function esc(texto) {
            return String(texto === null || texto === undefined ? '' : texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        $(function () {
            $('#filtro-contrato').select2({
                theme: 'bootstrap-5',
                placeholder: '— Todos —',
                allowClear: true,
                language: { noResults: function () { return 'No encontrado'; } },
                templateResult: function (data) {
                    if (!data.id) return data.text;
                    var cerrado = $(data.element).data('cerrado') == '1';
                    return $('<span class="d-flex align-items-center justify-content-between">')
                        .append($('<span>').text(data.text))
                        .append($('<span>')
                            .addClass(cerrado ? 'badge badge-danger ml-2' : 'badge badge-success ml-2')
                            .text(cerrado ? 'Finalizado' : 'Vigente')
                        );
                },
                templateSelection: function (data) {
                    if (!data.id) return data.text;
                    var cerrado = $(data.element).data('cerrado') == '1';
                    return $('<span>')
                        .append($('<span>').text(data.text))
                        .append($('<span>')
                            .addClass(cerrado ? 'badge badge-danger ml-2' : 'badge badge-success ml-2')
                            .text(cerrado ? 'Finalizado' : 'Vigente')
                        );
                }
            });

            $('#filtro-unidad').select2({
                theme: 'bootstrap-5',
                placeholder: '— Todas —',
                allowClear: true,
                language: { noResults: function () { return 'No encontrado'; } }
            });
        });

        function initDataTable() {
            if ($.fn.DataTable.isDataTable('#tabla')) {
                $('#tabla').DataTable().destroy();
            }
            $('#tabla').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: true,
                pagingType: "full_numbers",
                lengthMenu: [[50, 100, -1], [50, 100, "Todo"]],
                order: [[3, 'desc']], // ordena por defecto: Fecha Registro, descendente
                columnDefs: [
                    { orderable: false, targets: -1 } // última columna = Opciones
                ],
                language: {
                    sProcessing:   "Procesando...",
                    sLengthMenu:   "Mostrar _MENU_ registros",
                    sZeroRecords:  "No se encontraron resultados",
                    sEmptyTable:   "Ningún dato disponible en esta tabla",
                    sInfo:         "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    sInfoEmpty:    "Mostrando 0 a 0 de 0 registros",
                    sInfoFiltered: "(filtrado de _MAX_ registros)",
                    sSearch:       "Buscar:",
                    oPaginate: {
                        sFirst: "Primero", sLast: "Último",
                        sNext: "Siguiente", sPrevious: "Anterior"
                    }
                },
                dom:
                    "<'row align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-md-right'f>>" +
                    "tr" +
                    "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
            $('#tabla_length select').addClass('form-control form-control-sm');
            $('#tabla_filter input').addClass('form-control form-control-sm').css('display', 'inline-block');
        }

        function buscarConFiltros() {
            const contrato   = $('#filtro-contrato').val();
            const fechaDesde = $('#filtro-fecha-desde').val();
            const fechaHasta = $('#filtro-fecha-hasta').val();
            const material   = $('#filtro-material').val().trim();
            const unidad     = $('#filtro-unidad').val();

            const params = new URLSearchParams();
            if (contrato)   params.append('contrato',    contrato);
            if (fechaDesde) params.append('fecha_desde', fechaDesde);
            if (fechaHasta) params.append('fecha_hasta', fechaHasta);
            if (material)   params.append('material',    material);
            if (unidad)     params.append('unidad',      unidad);

            const url = params.toString() ? RUTA_TABLA + '?' + params.toString() : RUTA_TABLA;

            $('#div-instruccion').hide();
            $('#div-tabla').show();
            $('#tablaDatatable').html(
                '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'
            );

            $('#tablaDatatable').load(url, function () {
                initDataTable();
                const total = $('#tabla tbody tr').length;
                $('#badge-total').text(total + ' registros').show();
            });
        }

        window.recargar = function () { buscarConFiltros(); };

        function limpiarFiltros() {
            $('#filtro-contrato').val('').trigger('change');
            $('#filtro-unidad').val('').trigger('change');
            $('#filtro-fecha-desde').val('');
            $('#filtro-fecha-hasta').val('');
            $('#filtro-material').val('');
            $('#div-instruccion').show();
            $('#div-tabla').hide();
            $('#badge-total').hide();
        }

        // ── Editar encabezado del retiro ──────────────────────────────────
        function toggleDestinoEditar() {
            const marcado = $('#check-otra-editar').is(':checked');

            $('#contenedor-destino-editar').toggle(marcado);

            if (marcado) {
                $('#texto-descripcion-editar').text('(Requerida: indique el motivo)')
                    .removeClass('text-muted').addClass('text-danger');
            } else {
                $('#destino-editar').val('');
                $('#texto-descripcion-editar').text('(Opcional)')
                    .removeClass('text-danger').addClass('text-muted');
            }
        }

        function modalEditar(id) {
            openLoading();
            document.getElementById('formulario-editar').reset();

            axios.post(urlAdmin + '/admin/historial/contratos/salidas/informacion', { id: id })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        const r = response.data.retiro;

                        window.origenesRetiro = (response.data.origenes || []).map(String);

                        $('#id-editar').val(r.id);
                        $('#fecha-editar').val(r.fecha ? r.fecha.substring(0, 10) : '');
                        $('#no-factura-editar').val(r.no_factura ?? '');
                        $('#fecha-factura-editar').val(r.fecha_factura ? r.fecha_factura.substring(0, 10) : '');
                        $('#descripcion-editar').val(r.descripcion ?? '');

                        // Se bloquean como destino las unidades de origen de este retiro
                        $('#destino-editar option').each(function () {
                            const v = this.value;
                            $(this).prop('disabled', v !== '' && window.origenesRetiro.indexOf(v) !== -1);
                        });

                        $('#check-otra-editar').prop('checked', Number(r.otra_unidad) === 1);
                        toggleDestinoEditar();

                        if (Number(r.otra_unidad) === 1 && r.id_departamento_destino) {
                            $('#destino-editar').val(String(r.id_departamento_destino));
                        }

                        $('#modalEditar').modal('show');
                    } else {
                        toastr.error('No se pudo cargar la información');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al obtener información'); });
        }

        function editar() {
            const id            = $('#id-editar').val();
            const fecha         = $('#fecha-editar').val().trim();
            const noFactura     = $('#no-factura-editar').val().trim();
            const fechaFactura  = $('#fecha-factura-editar').val().trim();
            const descripcion   = $('#descripcion-editar').val().trim();
            const otraUnidad    = $('#check-otra-editar').is(':checked');
            const destino       = $('#destino-editar').val();

            if (fecha === '')             { toastr.error('La fecha es requerida'); return; }
            if (descripcion.length > 800) { toastr.error('Descripción máximo 800 caracteres'); return; }

            if (otraUnidad) {
                if (!destino) {
                    toastr.error('Seleccione la Unidad Destino');
                    return;
                }
                if (window.origenesRetiro.indexOf(String(destino)) !== -1) {
                    toastr.error('La Unidad Destino no puede ser igual a una Unidad de Origen de este retiro');
                    return;
                }
                if (descripcion === '') {
                    toastr.error('Indique en la descripción el motivo del retiro a otra unidad');
                    return;
                }
            }

            openLoading();
            const formData = new FormData();
            formData.append('id',                      id);
            formData.append('fecha',                   fecha);
            formData.append('no_factura',              noFactura);
            formData.append('fecha_factura',           fechaFactura);
            formData.append('descripcion',             descripcion);
            formData.append('otra_unidad',             otraUnidad ? 1 : 0);
            formData.append('id_departamento_destino', otraUnidad ? destino : '');

            axios.post(urlAdmin + '/admin/historial/contratos/salidas/editar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Retiro actualizado correctamente');
                        $('#modalEditar').modal('hide');
                        buscarConFiltros();
                    } else if (response.data.success === 2) {
                        Swal.fire({
                            title: 'Fecha inválida',
                            html:
                                'La fecha de retiro (<b>' + esc(response.data.fecha_salida) + '</b>) ' +
                                'es ' + esc(response.data.motivo) + ' (<b>' + esc(response.data.fecha_limite) + '</b>).',
                            icon: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
                    } else if (response.data.success === 3 || response.data.success === 4) {
                        toastr.error(response.data.mensaje);
                    } else if (response.data.success === 5) {
                        toastr.error('Indique en la descripción el motivo del retiro a otra unidad');
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al actualizar'); });
        }

        function eliminar(id) {
            Swal.fire({
                title: '¿Eliminar retiro?',
                text: 'Se eliminarán también todos los detalles relacionados. Esta acción no se puede deshacer.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.value) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/contratos/salidas/eliminar', { id: id })
                        .then((response) => {
                            closeLoading();
                            if (response.data.success === 1) {
                                toastr.success('Retiro eliminado correctamente');
                                buscarConFiltros();
                            } else if (response.data.success === 3) {
                                toastr.error(response.data.mensaje);
                            } else {
                                toastr.error('Error al eliminar');
                            }
                        })
                        .catch(() => { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }

        // ── Detalle del retiro ────────────────────────────────────────────
        // Los datos del retiro vienen en atributos data- del botón
        function verDetalle(id, btn) {
            const $b = $(btn);
            cargarDetalle(id, String($b.data('contrato') ?? ''), String($b.data('fecha') ?? ''), Number($b.data('cerrado')) === 1);
        }

        function cargarDetalle(id, contrato, fecha, cerrado) {
            window.idRetiroActual = id;
            window.detalleCtx     = { contrato: contrato, fecha: fecha, cerrado: cerrado };

            $('#detalle-contrato').text(contrato);
            $('#detalle-fecha').text(fecha);
            $('#detalle-tbody').html('');
            $('#detalle-contenido').hide();
            $('#detalle-destino').hide();
            $('#detalle-vacio').hide();
            $('#detalle-loading').show();

            $('#detalle-badge-cerrado').toggle(cerrado);

            $('#modalDetalle').modal('show');

            axios.post(urlAdmin + '/admin/historial/contratos/salidas/detalle', { id: id })
                .then((response) => {
                    $('#detalle-loading').hide();

                    if (response.data.success === 1 && response.data.detalle.length > 0) {
                        let html = '';

                        response.data.detalle.forEach((fila, index) => {
                            const botones = cerrado
                                ? '<span class="text-muted small">Contrato finalizado</span>'
                                : `
                                    <button type="button" class="btn btn-warning btn-sm" title="Editar"
                                            data-id="${Number(fila.id)}"
                                            data-cantidad="${Number(fila.cantidad_salida)}"
                                            data-material="${esc(fila.material)}"
                                            data-origen="${esc(fila.origen ?? '')}"
                                            onclick="abrirEditarItem(this)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm ml-1" title="Borrar"
                                            onclick="borrarItemDetalle(${Number(fila.id)})">
                                        <i class="fas fa-trash"></i>
                                    </button>`;

                            const origen = fila.origen
                                ? esc(fila.origen)
                                : '<span class="text-muted">Sin unidad</span>';

                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${esc(fila.material)}</td>
                                    <td class="text-center">${esc(fila.unidad)}</td>
                                    <td class="text-center">${origen}</td>
                                    <td class="text-center">${esc(fila.cantidad_salida)}</td>
                                    <td class="text-right">$${esc(fila.precio)}</td>
                                    <td class="text-right">$${esc(fila.subtotal)}</td>
                                    <td class="text-center">${botones}</td>
                                </tr>`;
                        });

                        $('#detalle-tbody').html(html);
                        $('#detalle-total').text('$' + response.data.total);

                        if (response.data.destino) {
                            $('#detalle-destino-nombre').text(response.data.destino);
                            $('#detalle-destino').show();
                        }

                        $('#detalle-contenido').show();
                    } else {
                        $('#detalle-vacio').show();
                    }
                })
                .catch(() => {
                    $('#detalle-loading').hide();
                    $('#detalle-vacio').show();
                    toastr.error('Error al cargar el detalle');
                });
        }

        function recargarDetalleActual() {
            if (window.idRetiroActual) {
                cargarDetalle(
                    window.idRetiroActual,
                    window.detalleCtx.contrato,
                    window.detalleCtx.fecha,
                    window.detalleCtx.cerrado
                );
            }
            buscarConFiltros();
        }

        function validateInputItem(event) {
            const key = event.key;
            if (["Backspace","ArrowLeft","ArrowRight","Delete","Tab"].includes(key)) return true;
            if (key === "e" || key === "E" || key === "-" || isNaN(Number(key))) return false;
            return true;
        }

        function abrirEditarItem(btn) {
            const $b = $(btn);

            $('#id-item-editar').val($b.data('id'));
            $('#cantidad-item-editar').val($b.data('cantidad'));
            $('#item-editar-material').text($b.data('material'));
            $('#item-editar-origen').text($b.data('origen') || 'Sin unidad');

            $('#modalEditarItem').modal('show');
        }

        function guardarEdicionItem() {
            const idItem   = $('#id-item-editar').val();
            const cantidad = Number($('#cantidad-item-editar').val());

            if (!idItem)                    { toastr.error('Ítem inválido'); return; }
            if (!cantidad || cantidad <= 0) { toastr.error('Ingresa una cantidad válida'); return; }

            openLoading();
            axios.post(urlAdmin + '/admin/historial/contratos/salidas/detalle/editar', {
                id_detalle: idItem,
                cantidad:   cantidad
            })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Ítem actualizado correctamente');
                        $('#modalEditarItem').modal('hide');
                        recargarDetalleActual();
                    } else if (response.data.success === 2) {
                        toastr.error('La cantidad supera lo disponible para la unidad (' + response.data.disponible + ')');
                    } else if (response.data.success === 3 || response.data.success === 4) {
                        toastr.error(response.data.mensaje);
                    } else {
                        toastr.error('Error al actualizar el ítem');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al actualizar el ítem'); });
        }

        function borrarItemDetalle(idItem) {
            Swal.fire({
                title: '¿Eliminar ítem?',
                text: 'Se eliminará este ítem del retiro. Esta acción no se puede deshacer.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.value) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/contratos/salidas/detalle/eliminar', { id_detalle: idItem })
                        .then((response) => {

                            closeLoading();
                            if (response.data.success === 1) {
                                toastr.success('Ítem eliminado correctamente');
                                recargarDetalleActual();
                            } else if (response.data.success === 3) {
                                toastr.error(response.data.mensaje);
                            } else {
                                toastr.error('Error al eliminar el ítem');
                            }
                        })
                        .catch(() => { closeLoading(); toastr.error('Error al eliminar el ítem'); });
                }
            });
        }

        function generarPdf(id) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = urlAdmin + '/admin/historial/contratos/salidas/pdf';
            form.target = '_blank';
            var fields = {
                '_token': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'id': id,
            };

            Object.keys(fields).forEach(function (key) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>
@endsection
