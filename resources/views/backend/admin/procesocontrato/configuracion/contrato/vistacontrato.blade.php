@extends('adminlte::page')

@section('title', 'Contrato')

@section('content_header')
    <h1>Contrato</h1>
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
                <i class="fas fa-user mr-2"></i>
                Editar Perfil
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

        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <button type="button" onclick="modalAgregar()" class="btn btn-dark btn-sm">
                        <i class="fas fa-plus-square"></i>
                        Nuevo Registro
                    </button>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">

                <!-- FILTROS -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Filtros</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Proveedor</label>
                                    <select class="form-control" id="filtro-proveedor" style="width: 100%">
                                        <option value="">Todos los proveedores</option>
                                        @foreach($arrayProveedor as $proveedor)
                                            <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select class="form-control" id="filtro-estado">
                                        <option value="">Todos</option>
                                        <option value="vigente">Vigente</option>
                                        <option value="finalizado">Finalizado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Fecha Inicio (desde)</label>
                                    <input type="date" class="form-control" id="filtro-fecha-desde">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Fecha Inicio (hasta)</label>
                                    <input type="date" class="form-control" id="filtro-fecha-hasta">
                                </div>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="button" class="btn btn-primary btn-block mb-1" onclick="filtrar()">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <button type="button" class="btn btn-default btn-block" onclick="limpiarFiltros()">
                                        <i class="fas fa-eraser"></i> Limpiar
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- LISTADO -->
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title">Listado</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="tablaDatatable">
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-filter fa-2x mb-2"></i>
                                        <p class="mb-0">Presione el botón <b>Filtrar</b> para cargar los contratos.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- MODAL NUEVO -->
        <div class="modal fade" id="modalAgregar">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Nuevo Contrato</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-nuevo" onsubmit="event.preventDefault(); nuevo();">
                            <div class="card-body">
                                <div class="row">

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Proveedor <span style="color: red">*</span></label>
                                            <select class="form-control select2-proveedor" id="proveedor-nuevo" style="width: 100%">
                                                <option value="">Seleccione un proveedor...</option>
                                                @foreach($arrayProveedor as $proveedor)
                                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Código</label>
                                            <input type="text" maxlength="300" class="form-control" id="codigo-nuevo" placeholder="CPB-" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Nombre de Proceso <span style="color: red">*</span></label>
                                            <input type="text" maxlength="300" class="form-control" id="nombre_proceso-nuevo" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Fecha Inicio <span style="color: red">*</span></label>
                                            <input type="date" class="form-control" id="fecha_inicio-nuevo">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Fecha Fin <span style="color: red">*</span></label>
                                            <input type="date" class="form-control" id="fecha_fin-nuevo">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Estado <span style="color: red">*</span></label>
                                            <select class="form-control" id="estado-nuevo">
                                                <option value="vigente">Vigente</option>
                                                <option value="finalizado">Finalizado</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Descripción</label>
                                            <textarea class="form-control" id="descripcion-nuevo" rows="3"></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="nuevo()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL EDITAR -->
        <div class="modal fade" id="modalEditar">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Editar Contrato</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <form id="formulario-editar" onsubmit="event.preventDefault(); editar();">
                            <div class="card-body">
                                <div class="row">

                                    <div class="form-group">
                                        <input type="hidden" id="id-editar">
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Proveedor <span style="color: red">*</span></label>
                                            <select class="form-control select2-proveedor-editar" id="proveedor-editar" style="width: 100%">
                                                <option value="">Seleccione un proveedor...</option>
                                                @foreach($arrayProveedor as $proveedor)
                                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Código</label>
                                            <input type="text" maxlength="300" class="form-control" id="codigo-editar" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Nombre de Proceso <span style="color: red">*</span></label>
                                            <input type="text" maxlength="300" class="form-control" id="nombre_proceso-editar" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Fecha Inicio <span style="color: red">*</span></label>
                                            <input type="date" class="form-control" id="fecha_inicio-editar">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Fecha Fin <span style="color: red">*</span></label>
                                            <input type="date" class="form-control" id="fecha_fin-editar">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Estado <span style="color: red">*</span></label>
                                            <select class="form-control" id="estado-editar">
                                                <option value="vigente">Vigente</option>
                                                <option value="finalizado">Finalizado</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Descripción</label>
                                            <textarea class="form-control" id="descripcion-editar" rows="3"></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="editar()">Guardar</button>
                    </div>
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

        var rutaTabla       = "{{ url('/admin/contrato/tabla/index') }}";
        var filtrosActuales = {};      // últimos filtros aplicados (para recargar tras guardar/editar)
        var tablaCargada    = false;   // la tabla solo se carga al presionar Filtrar

        // ── Select2 ───────────────────────────────────────────────────
        $(function () {
            $('#filtro-proveedor').select2({
                theme: "bootstrap-5",
                placeholder: 'Todos los proveedores',
                allowClear: true,
                width: '100%'
            });

            $('.select2-proveedor').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalAgregar'),
                placeholder: 'Buscar proveedor...',
                allowClear: true,
                width: '100%'
            });

            $('.select2-proveedor-editar').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalEditar'),
                placeholder: 'Buscar proveedor...',
                allowClear: true,
                width: '100%'
            });
        });

        // ── DataTable ─────────────────────────────────────────────────
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
                lengthMenu: [[100, 150, -1], [100, 150, "Todo"]],
                language: {
                    sProcessing: "Procesando...",
                    sLengthMenu: "Mostrar _MENU_ registros",
                    sZeroRecords: "No se encontraron resultados",
                    sEmptyTable: "Ningún dato disponible en esta tabla",
                    sInfo: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    sInfoEmpty: "Mostrando 0 a 0 de 0 registros",
                    sInfoFiltered: "(filtrado de _MAX_ registros)",
                    sSearch: "Buscar:",
                    oPaginate: {sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior"},
                    oAria: {sSortAscending: ": Orden ascendente", sSortDescending: ": Orden descendente"}
                },
                dom:
                    "<'row align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-md-right'f>>" +
                    "tr" +
                    "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });

            $('#tabla_length select').addClass('form-control form-control-sm');
            $('#tabla_filter input').addClass('form-control form-control-sm').css('display', 'inline-block');
        }

        // ── Carga de la tabla (con filtros opcionales) ────────────────
        function cargarTabla(filtros) {
            var url = rutaTabla;
            var query = $.param(filtros || {});
            if (query !== '') { url += '?' + query; }

            if ($.fn.DataTable.isDataTable('#tabla')) {
                $('#tabla').DataTable().destroy();
            }

            $('#tablaDatatable').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando contratos...</p>
                </div>
            `);

            $('#tablaDatatable').load(url, function (response, status) {
                if (status === 'error') {
                    $('#tablaDatatable').html('<div class="text-center py-5 text-danger">Error al cargar los contratos.</div>');
                    return;
                }
                initDataTable();
            });
        }

        // Recarga la tabla con los últimos filtros, solo si ya fue cargada con Filtrar
        window.recargar = function () {
            if (tablaCargada) {
                cargarTabla(filtrosActuales);
            }
        };

        // ── Filtros ───────────────────────────────────────────────────
        // Ningún filtro es obligatorio: sin datos en los filtros trae todos los contratos.
        function filtrar() {
            var id_proveedor = $('#filtro-proveedor').val();
            var estado       = $('#filtro-estado').val();
            var fecha_desde  = $('#filtro-fecha-desde').val();
            var fecha_hasta  = $('#filtro-fecha-hasta').val();

            // La validación se hace solo al presionar Filtrar
            if (fecha_desde !== '' && fecha_hasta !== '' && fecha_hasta < fecha_desde) {
                toastr.error('La fecha hasta no puede ser menor a la fecha desde');
                return;
            }

            var filtros = {};
            if (id_proveedor) { filtros.id_proveedor = id_proveedor; }
            if (estado)       { filtros.estado       = estado; }
            if (fecha_desde)  { filtros.fecha_desde  = fecha_desde; }
            if (fecha_hasta)  { filtros.fecha_hasta  = fecha_hasta; }

            filtrosActuales = filtros;
            tablaCargada    = true;

            cargarTabla(filtros);
        }

        function limpiarFiltros() {
            $('#filtro-proveedor').val(null).trigger('change');
            $('#filtro-estado').val('');
            $('#filtro-fecha-desde').val('');
            $('#filtro-fecha-hasta').val('');
        }

        // Muestra el primer error de validación del backend, o un mensaje por defecto
        function mostrarErrores(response, mensajeDefecto){
            if(response.data.success === 0 && response.data.errores){
                var primero = Object.values(response.data.errores)[0][0];
                toastr.error(primero);
            } else {
                toastr.error(mensajeDefecto);
            }
        }

        // ── Modal Agregar ─────────────────────────────────────────────
        function modalAgregar(){
            document.getElementById("formulario-nuevo").reset();
            $('.select2-proveedor').val(null).trigger('change');
            $('#modalAgregar').modal('show');
        }

        function nuevo(){
            var id_proveedor   = $('#proveedor-nuevo').val();
            var codigo         = document.getElementById('codigo-nuevo').value;
            var nombre_proceso = document.getElementById('nombre_proceso-nuevo').value;
            var fecha_inicio   = document.getElementById('fecha_inicio-nuevo').value;
            var fecha_fin      = document.getElementById('fecha_fin-nuevo').value;
            var estado         = document.getElementById('estado-nuevo').value;
            var descripcion    = document.getElementById('descripcion-nuevo').value;

            if(!id_proveedor){
                toastr.error('Debe seleccionar un proveedor');
                return;
            }
            if(nombre_proceso === ''){
                toastr.error('Nombre de proceso es requerido');
                return;
            }
            if(fecha_inicio === '' || fecha_fin === ''){
                toastr.error('Las fechas son requeridas');
                return;
            }
            if(fecha_fin < fecha_inicio){
                toastr.error('La fecha fin no puede ser menor a la fecha inicio');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('id_proveedor', id_proveedor);
            formData.append('codigo', codigo);
            formData.append('nombre_proceso', nombre_proceso);
            formData.append('fecha_inicio', fecha_inicio);
            formData.append('fecha_fin', fecha_fin);
            formData.append('estado', estado);
            formData.append('descripcion', descripcion);

            axios.post(urlAdmin+'/admin/contrato/nuevo', formData, {})
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        toastr.success('Registrado correctamente');
                        $('#modalAgregar').modal('hide');
                        recargar();
                    }
                    else {
                        mostrarErrores(response, 'Error al registrar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al registrar');
                    closeLoading();
                });
        }

        // ── Información / Editar ──────────────────────────────────────
        function informacion(id){
            openLoading();
            document.getElementById("formulario-editar").reset();
            $('.select2-proveedor-editar').val(null).trigger('change');

            axios.post(urlAdmin+'/admin/contrato/informacion',{
                'id': id
            })
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        var info = response.data.info;

                        $('#id-editar').val(info.id);
                        $('#codigo-editar').val(info.codigo);
                        $('#nombre_proceso-editar').val(info.nombre_proceso);

                        // substring(0, 10) por si el backend devuelve la fecha en formato ISO (con hora)
                        $('#fecha_inicio-editar').val((info.fecha_inicio || '').substring(0, 10));
                        $('#fecha_fin-editar').val((info.fecha_fin || '').substring(0, 10));

                        $('#estado-editar').val(info.estado);
                        $('#descripcion-editar').val(info.descripcion);

                        // El select ya trae todas las opciones desde el backend,
                        // solo seleccionamos el proveedor correspondiente.
                        if(info.id_proveedor){
                            $('#proveedor-editar').val(info.id_proveedor).trigger('change');
                        }

                        $('#modalEditar').modal('show');
                    }else{
                        toastr.error('Información no encontrada');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Información no encontrada');
                });
        }

        function editar(){
            var id             = document.getElementById('id-editar').value;
            var id_proveedor   = $('#proveedor-editar').val();
            var codigo         = document.getElementById('codigo-editar').value;
            var nombre_proceso = document.getElementById('nombre_proceso-editar').value;
            var fecha_inicio   = document.getElementById('fecha_inicio-editar').value;
            var fecha_fin      = document.getElementById('fecha_fin-editar').value;
            var estado         = document.getElementById('estado-editar').value;
            var descripcion    = document.getElementById('descripcion-editar').value;

            if(!id_proveedor){
                toastr.error('Debe seleccionar un proveedor');
                return;
            }
            if(nombre_proceso === ''){
                toastr.error('Nombre de proceso es requerido');
                return;
            }
            if(fecha_inicio === '' || fecha_fin === ''){
                toastr.error('Las fechas son requeridas');
                return;
            }
            if(fecha_fin < fecha_inicio){
                toastr.error('La fecha fin no puede ser menor a la fecha inicio');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('id', id);
            formData.append('id_proveedor', id_proveedor);
            formData.append('codigo', codigo);
            formData.append('nombre_proceso', nombre_proceso);
            formData.append('fecha_inicio', fecha_inicio);
            formData.append('fecha_fin', fecha_fin);
            formData.append('estado', estado);
            formData.append('descripcion', descripcion);

            axios.post(urlAdmin+'/admin/contrato/editar', formData, {})
                .then((response) => {
                    closeLoading();

                    if(response.data.success === 1){
                        toastr.success('Actualizado correctamente');
                        $('#modalEditar').modal('hide');
                        recargar();
                    }
                    else {
                        mostrarErrores(response, 'Error al actualizar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al actualizar');
                    closeLoading();
                });
        }

    </script>

@endsection
