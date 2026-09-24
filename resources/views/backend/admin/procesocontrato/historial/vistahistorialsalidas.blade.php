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
                            <div class="col-md-6">
                                <label class="font-weight-bold">
                                    <i class="fas fa-box mr-1 text-muted"></i> Buscar por ítem del contrato (nombre)
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="filtro-material"
                                       placeholder="Ej: cemento, servicio de transporte ...">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <small class="text-muted">
                                    Filtra los retiros que contengan ese ítem en su detalle.
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
                    <form id="formulario-editar">
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
                        <div class="form-group">
                            <label>Descripción</label>
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
        <div class="modal-dialog modal-lg" role="document">
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
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Ítem</th>
                                <th class="text-center">Unidad</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-right">Precio unitario</th>
                                <th class="text-center" style="width:110px">Opciones</th>
                            </tr>
                            </thead>
                            <tbody id="detalle-tbody"></tbody>
                        </table>
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
        <div class="modal-dialog modal-sm" role="document">
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

        window.idRetiroActual   = null;
        window.detalleCerrado   = false;

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

            const params = new URLSearchParams();
            if (contrato)   params.append('contrato',    contrato);
            if (fechaDesde) params.append('fecha_desde', fechaDesde);
            if (fechaHasta) params.append('fecha_hasta', fechaHasta);
            if (material)   params.append('material',    material);

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
            $('#filtro-fecha-desde').val('');
            $('#filtro-fecha-hasta').val('');
            $('#filtro-material').val('');
            $('#div-instruccion').show();
            $('#div-tabla').hide();
            $('#badge-total').hide();
        }

        function modalEditar(id) {
            openLoading();
            document.getElementById('formulario-editar').reset();

            axios.post(urlAdmin + '/admin/historial/contratos/salidas/informacion', { id: id })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        const r = response.data.retiro;
                        $('#id-editar').val(r.id);
                        $('#fecha-editar').val(r.fecha ? r.fecha.substring(0, 10) : '');
                        $('#no-factura-editar').val(r.no_factura ?? '');
                        $('#fecha-factura-editar').val(r.fecha_factura ? r.fecha_factura.substring(0, 10) : '');
                        $('#descripcion-editar').val(r.descripcion ?? '');
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

            if (fecha === '')             { toastr.error('La fecha es requerida'); return; }
            if (descripcion.length > 800) { toastr.error('Descripción máximo 800 caracteres'); return; }

            openLoading();
            const formData = new FormData();
            formData.append('id',             id);
            formData.append('fecha',          fecha);
            formData.append('no_factura',     noFactura);
            formData.append('fecha_factura',  fechaFactura);
            formData.append('descripcion',    descripcion);

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
                                'La fecha de retiro (<b>' + response.data.fecha_salida + '</b>) ' +
                                'es ' + response.data.motivo + ' (<b>' + response.data.fecha_limite + '</b>).',
                            icon: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
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
                            } else {
                                toastr.error('Error al eliminar');
                            }
                        })
                        .catch(() => { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }

        function verDetalle(id, contrato, fecha, cerrado) {
            window.idRetiroActual = id;
            window.detalleCerrado = !!cerrado;

            $('#detalle-contrato').text(contrato);
            $('#detalle-fecha').text(fecha);
            $('#detalle-tbody').html('');
            $('#detalle-contenido').hide();
            $('#detalle-vacio').hide();
            $('#detalle-loading').show();

            $('#detalle-badge-cerrado').toggle(!!cerrado);

            $('#modalDetalle').modal('show');

            axios.post(urlAdmin + '/admin/historial/contratos/salidas/detalle', { id: id })
                .then((response) => {
                    $('#detalle-loading').hide();
                    if (response.data.success === 1 && response.data.detalle.length > 0) {
                        let html = '';
                        response.data.detalle.forEach((fila, index) => {
                            const botones = window.detalleCerrado
                                ? '<span class="text-muted small">Contrato finalizado</span>'
                                : `
                                    <button type="button" class="btn btn-warning btn-sm" title="Editar"
                                            onclick="abrirEditarItem(${fila.id}, ${fila.cantidad_salida})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm ml-1" title="Borrar"
                                            onclick="borrarItemDetalle(${fila.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>`;

                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${fila.material}</td>
                                    <td class="text-center">${fila.unidad}</td>
                                    <td class="text-center">${fila.cantidad_salida}</td>
                                    <td class="text-right">$${fila.precio}</td>
                                    <td class="text-center">${botones}</td>
                                </tr>`;
                        });
                        $('#detalle-tbody').html(html);
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
                verDetalle(
                    window.idRetiroActual,
                    $('#detalle-contrato').text(),
                    $('#detalle-fecha').text(),
                    window.detalleCerrado
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

        function abrirEditarItem(idItem, cantidadActual) {
            $('#id-item-editar').val(idItem);
            $('#cantidad-item-editar').val(cantidadActual);
            $('#modalEditarItem').modal('show');
        }

        function guardarEdicionItem() {
            const idItem   = $('#id-item-editar').val();
            const cantidad = Number($('#cantidad-item-editar').val());

            if (!idItem)               { toastr.error('Ítem inválido'); return; }
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
                        toastr.error('La cantidad supera lo disponible en el contrato (' + response.data.disponible + ')');
                    } else if (response.data.success === 3) {
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
