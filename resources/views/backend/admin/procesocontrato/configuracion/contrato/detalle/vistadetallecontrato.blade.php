@extends('adminlte::page')

@section('title', 'Datos del Contrato')

@section('content_header')
    <h1>Datos del Contrato</h1>
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">
@endsection

@section('content')
    <div id="divcontenedor">

        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <a href="{{ route('admin.contrato.index') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                    <button type="button" onclick="modalAgregar()" class="btn btn-dark btn-sm">
                        <i class="fas fa-plus-square"></i>
                        Nuevo Registro
                    </button>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $contrato->codigo ?? 'S/C' }} - {{ $contrato->nombre_proceso }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="tablaDatatable">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MODAL NUEVO -->
        <div class="modal fade" id="modalAgregar">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Nuevo Item</h4>
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
                                            <label>Nombre <span style="color: red">*</span></label>
                                            <input type="text" maxlength="300" class="form-control" id="nombre-nuevo" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Unidad de Medida <span style="color: red">*</span></label>
                                            <select class="form-control" id="unidadmedida-nuevo">
                                                <option value="">Seleccione...</option>
                                                @foreach($arrayUnidadMedida as $um)
                                                    <option value="{{ $um->id }}">{{ $um->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Cantidad <span style="color: red">*</span></label>
                                            <input type="number" min="1" max="1000000" step="1" class="form-control" id="cantidad-nuevo" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Precio <span style="color: red">*</span></label>
                                            <input type="number" min="0" max="1000000" step="0.0001" class="form-control" id="precio-nuevo" autocomplete="off">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary" onclick="nuevo()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL EDITAR -->
        <div class="modal fade" id="modalEditar">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Editar Item</h4>
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
                                            <label>Nombre <span style="color: red">*</span></label>
                                            <input type="text" maxlength="300" class="form-control" id="nombre-editar" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Unidad de Medida <span style="color: red">*</span></label>
                                            <select class="form-control" id="unidadmedida-editar">
                                                <option value="">Seleccione...</option>
                                                @foreach($arrayUnidadMedida as $um)
                                                    <option value="{{ $um->id }}">{{ $um->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Cantidad <span style="color: red">*</span></label>
                                            <input type="number" min="1" max="1000000" step="1" class="form-control" id="cantidad-editar" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Precio <span style="color: red">*</span></label>
                                            <input type="number" min="0" max="1000000" step="0.0001" class="form-control" id="precio-editar" autocomplete="off">
                                        </div>
                                    </div>


                                    <hr>
                                    <p style="color: red">Solo podra Editar o Borrar sino ha retirado</p>

                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary" onclick="editar()">Guardar</button>
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
        $(function () {
            const idContrato = {{ $contrato->id }};
            const ruta = "{{ url('/admin/contrato/detalle/tabla') }}/" + idContrato;

            window.idContratoActual = idContrato;

            // ─────────────────────────────────────────────────────────────
            // FIX: poder escribir en el buscador de Select2 dentro de modales
            // (Bootstrap 4 fuerza el foco dentro del modal y bloquea el input
            // de búsqueda porque la lista se dibuja fuera del modal).
            // ─────────────────────────────────────────────────────────────

            // 1) Desactivar el "enforceFocus" del modal
            if ($.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function () {};
            }

            // 2) Por seguridad, quitar el evento que devuelve el foco al modal
            $('#modalAgregar, #modalEditar').on('shown.bs.modal', function () {
                $(document).off('focusin.bs.modal');
            });

            // 3) Enfocar el campo de búsqueda apenas se abre la lista
            $(document).on('select2:open', function () {
                setTimeout(function () {
                    var campo = document.querySelector('.select2-container--open .select2-search__field');
                    if (campo) campo.focus();
                }, 0);
            });

            // Select2 con el mismo tema y configuración usada en Registro de Salidas
            $('#unidadmedida-nuevo').select2({
                theme: "bootstrap-5",
                language: {
                    noResults: function () {
                        return "Búsqueda no encontrada";
                    }
                }
            });

            $('#unidadmedida-editar').select2({
                theme: "bootstrap-5",
                language: {
                    noResults: function () {
                        return "Búsqueda no encontrada";
                    }
                }
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

            function cargarTabla() {
                $('#tablaDatatable').load(ruta, function () {
                    initDataTable();
                });
            }

            cargarTabla();

            window.recargar = function () {
                cargarTabla();
            };
        });
    </script>

    <script>

        function modalAgregar(){
            document.getElementById("formulario-nuevo").reset();
            $('#unidadmedida-nuevo').val('').trigger('change');
            $('#modalAgregar').modal('show');
        }

        function nuevo(){
            var nombre        = document.getElementById('nombre-nuevo').value;
            var idUnidadMedida = $('#unidadmedida-nuevo').val();
            var cantidad      = document.getElementById('cantidad-nuevo').value;
            var precio        = document.getElementById('precio-nuevo').value;

            if(nombre === ''){
                toastr.error('Nombre es requerido');
                return;
            }
            if(idUnidadMedida === '' || idUnidadMedida === null){
                toastr.error('Unidad de Medida es requerida');
                return;
            }
            if(cantidad === '' || Number(cantidad) <= 0){
                toastr.error('Cantidad es requerida');
                return;
            }
            if(Number(cantidad) > 1000000){
                toastr.error('Cantidad no puede ser mayor a 1,000,000');
                return;
            }
            if(precio === '' || Number(precio) < 0){
                toastr.error('Precio es requerido');
                return;
            }
            if(Number(precio) > 1000000){
                toastr.error('Precio no puede ser mayor a 1,000,000');
                return;
            }

            precio = Number(precio).toFixed(4);

            openLoading();
            var formData = new FormData();
            formData.append('id_contrato', window.idContratoActual);
            formData.append('nombre', nombre);
            formData.append('id_unidadmedida', idUnidadMedida);
            formData.append('cantidad', cantidad);
            formData.append('precio', precio);

            axios.post(urlAdmin+'/admin/contrato/detalle/nuevo', formData, {})
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        toastr.success('Registrado correctamente');
                        $('#modalAgregar').modal('hide');
                        recargar();
                    }
                    else {
                        toastr.error('Error al registrar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al registrar');
                    closeLoading();
                });
        }

        function informacion(id){
            openLoading();
            document.getElementById("formulario-editar").reset();
            $('#unidadmedida-editar').val('').trigger('change');

            axios.post(urlAdmin+'/admin/contrato/detalle/informacion',{
                'id': id
            })
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        var info = response.data.info;

                        $('#id-editar').val(info.id);
                        $('#nombre-editar').val(info.nombre);
                        $('#unidadmedida-editar').val(info.id_unidadmedida).trigger('change');
                        $('#cantidad-editar').val(info.cantidad);
                        $('#precio-editar').val(info.precio);

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
            var nombre         = document.getElementById('nombre-editar').value;
            var idUnidadMedida = $('#unidadmedida-editar').val();
            var cantidad       = document.getElementById('cantidad-editar').value;
            var precio         = document.getElementById('precio-editar').value;

            if(nombre === ''){
                toastr.error('Nombre es requerido');
                return;
            }
            if(idUnidadMedida === '' || idUnidadMedida === null){
                toastr.error('Unidad de Medida es requerida');
                return;
            }
            if(cantidad === '' || Number(cantidad) <= 0){
                toastr.error('Cantidad es requerida');
                return;
            }
            if(Number(cantidad) > 1000000){
                toastr.error('Cantidad no puede ser mayor a 1,000,000');
                return;
            }
            if(precio === '' || Number(precio) < 0){
                toastr.error('Precio es requerido');
                return;
            }
            if(Number(precio) > 1000000){
                toastr.error('Precio no puede ser mayor a 1,000,000');
                return;
            }

            precio = Number(precio).toFixed(4);

            openLoading();
            var formData = new FormData();
            formData.append('id', id);
            formData.append('nombre', nombre);
            formData.append('id_unidadmedida', idUnidadMedida);
            formData.append('cantidad', cantidad);
            formData.append('precio', precio);

            axios.post(urlAdmin+'/admin/contrato/detalle/editar', formData, {})
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        toastr.success('Actualizado correctamente');
                        $('#modalEditar').modal('hide');
                        recargar();
                    }
                    else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al actualizar');
                    closeLoading();
                });
        }

        function eliminar(id){
            Swal.fire({
                title: '¿Está seguro?',
                text: 'Esta acción no se puede deshacer',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (result.value) {

                    openLoading();

                    axios.post(urlAdmin + '/admin/contrato/detalle/eliminar', {
                        id: id
                    })
                        .then((response) => {
                            closeLoading();

                            if(response.data.success === 1){
                                toastr.success('Eliminado correctamente');
                                recargar();
                            } else {
                                toastr.error(response.data.message ?? 'Error al eliminar');
                            }
                        })
                        .catch((error) => {
                            closeLoading();

                            console.log(error.response);

                            toastr.error('Error al eliminar');
                        });
                }
            });
        }

    </script>

@endsection
