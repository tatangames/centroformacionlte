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
            <div class="modal-dialog modal-xl">
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

                                    <div class="col-md-6">
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
                                            <label>Unidades Asignadas <span style="color: red">*</span></label>
                                            <select class="form-control" id="departamento-nuevo" multiple="multiple">
                                                @foreach($arrayDepartamentos as $dep)
                                                    <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
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
                        <button type="button" class="btn btn-primary" onclick="nuevo()">Continuar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL EDITAR -->
        <div class="modal fade" id="modalEditar">
            <div class="modal-dialog modal-xl">
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

                                    <div class="col-md-6">
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
                                            <label>Unidades Asignadas <span style="color: red">*</span></label>
                                            <select class="form-control" id="departamento-editar" multiple="multiple">
                                                @foreach($arrayDepartamentos as $dep)
                                                    <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
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
                                    <p style="color: red; font-weight: bold">Solo podra Editar o Borrar sino ha retirado</p>

                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="editar()">Continuar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DISTRIBUIR -->
        <div class="modal fade" id="modalDistribuir" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Distribuir cantidad entre unidades</h4>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1"><b>Item:</b> <span id="dist-item"></span></p>
                        <p class="mb-2">
                            <b>Total:</b> <span id="dist-total"></span> &nbsp;|&nbsp;
                            <b>Asignado:</b> <span id="dist-asignado">0</span> &nbsp;|&nbsp;
                            <b>Restante:</b> <span id="dist-restante" style="font-weight:bold">0</span>
                        </p>

                        <table class="table table-bordered table-sm">
                            <thead>
                            <tr>
                                <th>Unidad</th>
                                <th style="width:200px">Cantidad</th>
                            </tr>
                            </thead>
                            <tbody id="dist-body"></tbody>
                        </table>

                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="repartirEquitativo()">
                            <i class="fas fa-equals"></i> Repartir equitativamente
                        </button>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" onclick="volverDistribucion()">Volver</button>
                        <button type="button" class="btn btn-primary" id="dist-guardar" onclick="guardarDistribucion()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DETALLE -->
        <div class="modal fade" id="modalDetalle">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Detalle del Item</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="contenido-detalle">
                    </div>
                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL SALIDAS -->
        <div class="modal fade" id="modalSalidas">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Salidas del Item</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="contenido-salidas">
                    </div>
                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
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

            // FIX: poder escribir en el buscador de Select2 dentro de modales
            if ($.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function () {};
            }

            $('#modalAgregar, #modalEditar').on('shown.bs.modal', function () {
                $(document).off('focusin.bs.modal');
            });

            $(document).on('select2:open', function () {
                setTimeout(function () {
                    var campo = document.querySelector('.select2-container--open .select2-search__field');
                    if (campo) campo.focus();
                }, 0);
            });

            // Select2 (mismo tema y configuración para todos los selects)
            const configSelect2 = {
                theme: "bootstrap-5",
                width: '100%',
                language: {
                    noResults: function () {
                        return "Búsqueda no encontrada";
                    }
                }
            };

            $('#unidadmedida-nuevo').select2(configSelect2);
            $('#unidadmedida-editar').select2(configSelect2);

            // Selección múltiple para las unidades asignadas
            const configSelect2Multiple = Object.assign({}, configSelect2, {
                placeholder: 'Seleccione una o varias unidades',
                closeOnSelect: false
            });

            $('#departamento-nuevo').select2(configSelect2Multiple);
            $('#departamento-editar').select2(configSelect2Multiple);

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

        var distModo = null;          // 'nuevo' | 'editar'
        var distDatos = null;         // datos del item a guardar
        var distOrigen = null;        // modal al que se vuelve
        var distPreviasEditar = {};   // cantidades guardadas por unidad (al editar)

        function modalAgregar(){
            document.getElementById("formulario-nuevo").reset();
            $('#unidadmedida-nuevo').val('').trigger('change');
            $('#departamento-nuevo').val(null).trigger('change');
            $('#modalAgregar').modal('show');
        }

        // Valida el formulario (sufijo: 'nuevo' | 'editar'). Devuelve los datos o null.
        function validarFormulario(sufijo){
            var nombre          = document.getElementById('nombre-' + sufijo).value;
            var idUnidadMedida  = $('#unidadmedida-' + sufijo).val();
            var idDepartamentos = $('#departamento-' + sufijo).val() || [];
            var cantidad        = document.getElementById('cantidad-' + sufijo).value;
            var precio          = document.getElementById('precio-' + sufijo).value;

            if(nombre === ''){ toastr.error('Nombre es requerido'); return null; }
            if(idUnidadMedida === '' || idUnidadMedida === null){ toastr.error('Unidad de Medida es requerida'); return null; }
            if(idDepartamentos.length === 0){ toastr.error('Seleccione al menos una Unidad Asignada'); return null; }
            if(cantidad === '' || Number(cantidad) <= 0 || !Number.isInteger(Number(cantidad))){ toastr.error('Cantidad es requerida (número entero)'); return null; }
            if(Number(cantidad) > 1000000){ toastr.error('Cantidad no puede ser mayor a 1,000,000'); return null; }
            if(Number(cantidad) < idDepartamentos.length){ toastr.error('La cantidad no alcanza para asignar al menos 1 a cada unidad'); return null; }
            if(precio === '' || Number(precio) < 0){ toastr.error('Precio es requerido'); return null; }
            if(Number(precio) > 1000000){ toastr.error('Precio no puede ser mayor a 1,000,000'); return null; }

            return {
                nombre: nombre,
                id_unidadmedida: idUnidadMedida,
                cantidad: Number(cantidad),
                precio: Number(precio).toFixed(4)
            };
        }

        function nuevo(){
            var datos = validarFormulario('nuevo');
            if(!datos) return;

            datos.id_contrato = window.idContratoActual;
            distModo = 'nuevo';
            distDatos = datos;
            abrirDistribucion('#departamento-nuevo', {}, '#modalAgregar');
        }

        function editar(){
            var datos = validarFormulario('editar');
            if(!datos) return;

            datos.id = document.getElementById('id-editar').value;
            distModo = 'editar';
            distDatos = datos;
            abrirDistribucion('#departamento-editar', distPreviasEditar, '#modalEditar');
        }

        function abrirDistribucion(selectId, previas, modalOrigen){
            distOrigen = modalOrigen;

            var html = '';
            $(selectId + ' option:selected').each(function(){
                var id = $(this).val();
                var valor = (previas[id] !== undefined) ? previas[id] : '';
                html += '<tr data-id="' + id + '">' +
                    '<td>' + $('<div>').text($(this).text()).html() + '</td>' +
                    '<td><input type="number" min="1" max="' + distDatos.cantidad + '" step="1" class="form-control form-control-sm dist-input" value="' + valor + '"></td>' +
                    '</tr>';
            });

            $('#dist-body').html(html);
            $('#dist-item').text(distDatos.nombre);
            $('#dist-total').text(distDatos.cantidad);
            calcularRestante();

            $(modalOrigen).one('hidden.bs.modal', function(){
                $('#modalDistribuir').modal('show');
            });
            $(modalOrigen).modal('hide');
        }

        // Bloquea caracteres que no sean dígitos (e, +, -, punto, coma)
        $(document).on('keydown', '.dist-input', function(e){
            if (['e', 'E', '+', '-', '.', ','].indexOf(e.key) !== -1) {
                e.preventDefault();
            }
        });

        // Limita el valor para que la suma nunca supere el total
        $(document).on('input', '.dist-input', function(){
            var $input = $(this);
            var valor = $input.val();

            if (valor !== '') {
                var n = Math.floor(Number(valor));
                if (isNaN(n) || n < 0) n = 0;

                // suma de las demás unidades
                var otros = 0;
                $('.dist-input').not(this).each(function(){
                    otros += Math.floor(Number($(this).val())) || 0;
                });

                var maximo = Math.max(distDatos.cantidad - otros, 0);

                if (n > maximo) {
                    n = maximo;
                    toastr.warning('No puede superar el total (' + distDatos.cantidad + '). Máximo para esta unidad: ' + maximo);
                }

                if (String(n) !== valor) {
                    $input.val(n);
                }
            }

            calcularRestante();
        });

        function calcularRestante(){
            var suma = 0, validos = true;

            $('.dist-input').each(function(){
                var v = $(this).val();
                if(v === '' || !Number.isInteger(Number(v)) || Number(v) < 1){
                    validos = false;
                }
                suma += Number(v) || 0;
            });

            var restante = distDatos.cantidad - suma;

            $('#dist-asignado').text(suma);
            $('#dist-restante').text(restante).css('color', restante === 0 ? 'green' : 'red');

            $('#dist-guardar').prop('disabled', !(restante === 0 && validos));
        }

        function repartirEquitativo(){
            var inputs = $('.dist-input');
            var n = inputs.length;
            var base = Math.floor(distDatos.cantidad / n);
            var resto = distDatos.cantidad % n;

            inputs.each(function(i){
                $(this).val(base + (i < resto ? 1 : 0));
            });
            calcularRestante();
        }

        function volverDistribucion(){
            $('#modalDistribuir').one('hidden.bs.modal', function(){
                $(distOrigen).modal('show');
            });
            $('#modalDistribuir').modal('hide');
        }

        function guardarDistribucion(){
            var departamentos = [];
            var suma = 0;

            $('#dist-body tr').each(function(){
                var cant = Number($(this).find('.dist-input').val());
                suma += cant;
                departamentos.push({
                    id: $(this).data('id'),
                    cantidad: cant
                });
            });

            // Verificación final antes de enviar
            if (suma !== distDatos.cantidad) {
                toastr.error('La suma (' + suma + ') debe ser igual al total (' + distDatos.cantidad + ')');
                return;
            }

            var payload = Object.assign({}, distDatos, { departamentos: departamentos });
            var url = urlAdmin + '/admin/contrato/detalle/' + (distModo === 'nuevo' ? 'nuevo' : 'editar');

            openLoading();

            axios.post(url, payload)
                .then((response) => {
                    closeLoading();

                    if(response.data.success === 1){
                        toastr.success(distModo === 'nuevo' ? 'Registrado correctamente' : 'Actualizado correctamente');
                        $('#modalDistribuir').modal('hide');
                        recargar();
                    }
                    else if(response.data.success === 2){
                        // Tiene retiros: no se puede editar
                        $('#modalDistribuir').modal('hide');
                        Swal.fire({
                            title: 'No se puede editar',
                            text: response.data.message,
                            type: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    }
                    else if(response.data.success === 3){
                        toastr.error(response.data.message);
                    }
                    else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al guardar');
                });
        }

        function informacion(id){
            openLoading();
            document.getElementById("formulario-editar").reset();
            $('#unidadmedida-editar').val('').trigger('change');
            $('#departamento-editar').val(null).trigger('change');

            axios.post(urlAdmin+'/admin/contrato/detalle/informacion',{
                'id': id
            })
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        var info = response.data.info;
                        var distribucion = response.data.distribucion || {};
                        distPreviasEditar = distribucion;
                        var idsDepartamentos = Object.keys(distribucion);

                        $('#id-editar').val(info.id);
                        $('#nombre-editar').val(info.nombre);
                        $('#unidadmedida-editar').val(info.id_unidadmedida).trigger('change');
                        $('#departamento-editar').val(idsDepartamentos).trigger('change');
                        $('#cantidad-editar').val(info.cantidad);
                        $('#precio-editar').val(info.precio);

                        $('#modalEditar').modal('show');
                    }
                    else{
                        toastr.error('Información no encontrada');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Información no encontrada');
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
                            }
                            else if(response.data.success === 2){
                                // Tiene retiros: no se puede borrar
                                Swal.fire({
                                    title: 'No se puede eliminar',
                                    text: response.data.message,
                                    type: 'error',
                                    confirmButtonText: 'Entendido'
                                });
                            }
                            else {
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

        function verDetalle(id){
            $('#contenido-detalle').html('<p class="text-center text-muted my-4">Cargando...</p>');
            $('#modalDetalle').modal('show');

            $('#contenido-detalle').load("{{ url('/admin/contrato/detalle/completo') }}/" + id, function (response, status) {
                if (status === 'error') {
                    $('#contenido-detalle').html('<p class="text-center text-danger my-4">Error al cargar el detalle</p>');
                }
            });
        }

        function verSalidas(id){
            $('#contenido-salidas').html('<p class="text-center text-muted my-4">Cargando...</p>');
            $('#modalSalidas').modal('show');

            $('#contenido-salidas').load("{{ url('/admin/contrato/detalle/salidas') }}/" + id, function (response, status) {
                if (status === 'error') {
                    $('#contenido-salidas').html('<p class="text-center text-danger my-4">Error al cargar las salidas</p>');
                }
            });
        }

    </script>

@endsection
