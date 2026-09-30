@extends('adminlte::page')

@section('title', 'Agregar Extras — Retiro #' . $retiro->id)

@section('content_header')
    <h1>Agregar Extras a Retiro</h1>
@stop

@section('plugins.Sweetalert2', true)
@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>

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
    <style>
        table { table-layout: fixed; }
        *:focus { outline: none; }
    </style>

    <div id="divcontenedor">

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-10">
                        <div class="card card-gray-dark">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Retiro #{{ $retiro->id }} —
                                    {{ $retiro->contrato->nombre_proceso ?? '' }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="text-muted">Fecha</label>
                                        <p><strong>{{ date('d/m/Y', strtotime($retiro->fecha)) }}</strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted">Destino</label>
                                        <p>
                                            @if($retiro->otra_unidad)
                                                <span class="badge badge-warning">Otra unidad</span>
                                                <strong>{{ $retiro->departamentoDestino->nombre ?? '' }}</strong>
                                            @else
                                                <strong>-</strong>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted">Descripción</label>
                                        <p><strong>{{ $retiro->descripcion ?? '' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="content-header">
            <div class="row" style="margin-left: 0">
                <button type="button" onclick="abrirModal()" class="btn btn-primary btn-sm">
                    <i class="fas fa-search mr-1"></i> Buscar Ítem del Contrato
                </button>
                <a href="{{ route('admin.historial.contratos.index') }}"
                   class="btn btn-secondary btn-sm ml-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </section>

        {{-- Modal buscar ítem --}}
        <div class="modal fade" id="modalRepuesto">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background:#2156af">
                        <h4 class="modal-title" style="color:#fff">
                            <i class="fas fa-search mr-2"></i>Buscar Ítem del Contrato
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <div class="form-group">
                            <label>Unidad de Origen <span class="text-danger">*</span></label>
                            <select class="form-control" id="select-unidad-extra" onchange="cambioUnidad()">
                                <option value="">Seleccionar Unidad de Origen</option>
                                @foreach($arrayUnidades as $u)
                                    <option value="{{ $u->id }}">{{ $u->nombre }}</option>
                                @endforeach
                            </select>
                            @if($retiro->otra_unidad)
                                <small class="text-muted">
                                    No se muestra la unidad destino de este retiro.
                                </small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Ítem — Solo con disponibilidad en la unidad</label>
                            <input id="inputBuscador" autocomplete="off"
                                   class="form-control" style="width:100%"
                                   onkeyup="buscarMaterial(this)"
                                   onfocus="buscarMaterial(this)"
                                   maxlength="300" type="text"
                                   placeholder="Escribir nombre del ítem o dejar vacío para ver todos…">
                        </div>
                        <div class="list-group" id="listaResultados" style="max-height:300px; overflow-y:auto;"></div>

                        <div id="formCantidad" style="display:none" class="mt-3 border-top pt-3">
                            <input type="hidden" id="id-item-seleccionado">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label>Ítem</label>
                                    <input type="text" disabled class="form-control" id="info-item">
                                </div>
                                <div class="col-md-3">
                                    <label>Disponible en la unidad</label>
                                    <input type="text" disabled class="form-control" id="info-disponible">
                                </div>
                                <div class="col-md-3">
                                    <label>Cantidad a retirar</label>
                                    <input type="number" min="1" class="form-control" id="input-cantidad"
                                           onkeydown="return validateInput(event);"
                                           oninput="validateCantidadSalida(this)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" id="btnAgregar" style="display:none" onclick="agregarAlDetalle()">
                            <i class="fas fa-plus mr-1"></i> Agregar al Detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6" style="margin-left: 15px">
                    <h2>Ítems a Retirar</h2>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-gray-dark">
                    <div class="card-header">
                        <h3 class="card-title">Detalle</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" id="matriz">
                                <thead>
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:33%">Ítem</th>
                                    <th style="width:12%">U/M</th>
                                    <th style="width:20%">Unidad de Origen</th>
                                    <th style="width:15%">Cantidad</th>
                                    <th style="width:15%">Opciones</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="modal-footer justify-content-between" style="margin-top: 25px;">
            <button type="button" class="btn btn-success" onclick="preguntaGuardar()">
                <i class="fas fa-save mr-1"></i> Guardar Extras
            </button>
        </div>

    </div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script>
        const ID_RETIRO   = {{ $retiro->id }};
        const ID_CONTRATO = {{ $retiro->id_contrato ?? 'null' }};

        window.disponibleActual    = 0;
        window.unidadMedida        = '';
        window.nombreItem          = '';
        window.buscadorTimer       = null;
        window.buscadorSeq         = 0;
        window.clavesAgregadas     = new Set(); // "idItem-idUnidad" ya agregados en esta sesión

        // ── Helper: escapar texto para insertarlo en HTML ──
        function esc(texto) {
            return String(texto === null || texto === undefined ? '' : texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function abrirModal() {
            document.getElementById('inputBuscador').value = '';
            document.getElementById('listaResultados').innerHTML = '';
            document.getElementById('formCantidad').style.display = 'none';
            document.getElementById('btnAgregar').style.display = 'none';
            $('#modalRepuesto').modal('show');
        }

        function validateInput(event) {
            const key = event.key;
            if (["Backspace","ArrowLeft","ArrowRight","Delete","Tab"].includes(key)) return true;
            if (key === "e" || key === "E" || key === "-" || isNaN(Number(key))) return false;
            return true;
        }

        function validateCantidadSalida(input) {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (Number(input.value) > window.disponibleActual) input.value = window.disponibleActual;
        }

        // ── Al cambiar la unidad de origen se reinicia la búsqueda ──
        function cambioUnidad() {
            $('#formCantidad').hide();
            $('#btnAgregar').hide();
            $('#id-item-seleccionado').val('');
            $('#listaResultados').empty();

            if ($('#select-unidad-extra').val()) {
                buscarMaterial(document.getElementById('inputBuscador'));
            }
        }

        // ── Buscar ítem (con espera de 250 ms entre teclas) ──
        function buscarMaterial(e) {
            clearTimeout(window.buscadorTimer);
            window.buscadorTimer = setTimeout(function () { ejecutarBusqueda(e); }, 250);
        }

        function ejecutarBusqueda(e) {
            const idUnidad = $('#select-unidad-extra').val();

            if (!idUnidad) {
                $('#listaResultados').html('<span class="text-muted p-2 d-block">Seleccione primero la unidad de origen</span>');
                return;
            }

            const seq = ++window.buscadorSeq;

            axios.post(urlAdmin + '/admin/buscar/contrato-detalle/disponible', {
                query:           e.value,
                id_contrato:     ID_CONTRATO,
                id_departamento: idUnidad
            })
                .then((response) => {
                    // Si ya hay una búsqueda más nueva, se ignora esta respuesta
                    if (seq !== window.buscadorSeq) return;
                    renderResultados(response.data, idUnidad);
                })
                .catch(() => { toastr.error('Error al buscar'); });
        }

        function renderResultados(items, idUnidad) {
            const $lista = $('#listaResultados');
            $lista.empty();

            if (!items || items.length === 0) {
                $lista.html('<span class="text-muted p-2 d-block">Sin resultados con disponibilidad en esta unidad</span>');
                return;
            }

            items.forEach(function (item) {
                const clave      = String(item.id) + '-' + String(idUnidad);
                const yaAgregado = window.clavesAgregadas.has(clave);

                const $a = $('<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"></a>')
                    .toggleClass('disabled text-muted', yaAgregado)
                    .css('pointer-events', yaAgregado ? 'none' : 'auto');

                const $izquierda = $('<span></span>')
                    .append($('<span></span>').text(item.nombre))
                    .append($('<span class="badge badge-light ml-2"></span>').text(item.unidad || '—'));

                const $derecha = $('<span></span>')
                    .addClass(yaAgregado ? 'badge badge-secondary' : 'badge badge-info')
                    .text(yaAgregado ? 'Ya agregado' : 'Disp: ' + item.disponible);

                $a.append($izquierda).append($derecha);

                if (!yaAgregado) {
                    $a.on('click', function (ev) {
                        ev.preventDefault();
                        seleccionarItem(item);
                    });
                }

                $lista.append($a);
            });
        }

        function seleccionarItem(item) {
            $('#id-item-seleccionado').val(item.id);
            $('#info-item').val(item.nombre + (item.unidad ? ' (' + item.unidad + ')' : ''));
            $('#info-disponible').val(item.disponible);
            $('#input-cantidad').val('').attr('max', item.disponible);

            window.disponibleActual = Number(item.disponible);
            window.unidadMedida     = item.unidad || '';
            window.nombreItem       = item.nombre;

            $('#formCantidad').show();
            $('#btnAgregar').show();
            $('#input-cantidad').trigger('focus');
        }

        function agregarAlDetalle() {
            const id           = $('#id-item-seleccionado').val();
            const idUnidad     = $('#select-unidad-extra').val();
            const nombreUnidad = $('#select-unidad-extra option:selected').text();
            const cantidad     = Number($('#input-cantidad').val());
            const clave        = String(id) + '-' + String(idUnidad);

            if (!idUnidad) { toastr.error('Seleccione la unidad de origen'); return; }
            if (!id) { toastr.error('Selecciona un ítem'); return; }
            if (!cantidad || cantidad <= 0) { toastr.error('Ingresa una cantidad válida'); return; }
            if (cantidad > window.disponibleActual) { toastr.error('Supera la cantidad disponible'); return; }
            if (window.clavesAgregadas.has(clave)) { toastr.error('Este ítem ya fue agregado para esta unidad'); return; }

            window.clavesAgregadas.add(clave);

            var nFilas = $('#matriz > tbody > tr').length + 1;
            var markup = "<tr>" +
                "<td><p id='fila" + nFilas + "' class='form-control' style='max-width:55px'>" + nFilas + "</p></td>" +
                "<td>" +
                "<input name='idItemArray[]' type='hidden' data-iditemarray='" + Number(id) + "' data-idunidad='" + Number(idUnidad) + "' data-clave='" + clave + "'>" +
                "<input disabled value='" + esc(window.nombreItem) + "' title='" + esc(window.nombreItem) + "' class='form-control form-control-sm' type='text'>" +
                "</td>" +
                "<td><input disabled value='" + esc(window.unidadMedida || '—') + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><input disabled value='" + esc(nombreUnidad) + "' title='" + esc(nombreUnidad) + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><input name='cantidadArray[]' disabled data-cantidad='" + cantidad + "' value='" + cantidad + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><button type='button' class='btn btn-danger btn-sm btn-block' onclick='borrarFila(this, \"" + clave + "\")'>Borrar</button></td>" +
                "</tr>";
            $("#matriz tbody").append(markup);

            $('#modalRepuesto').modal('hide');
            toastr.success('Agregado al detalle');
        }

        function preguntaGuardar() {
            if ($('#matriz > tbody > tr').length === 0) {
                toastr.error('Agrega al menos un ítem');
                return;
            }
            Swal.fire({
                title: '¿Guardar ítems extras?',
                text: '',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, guardar'
            }).then((result) => {
                if (result.value) guardarExtras();
            });
        }

        function guardarExtras() {
            var idItem    = $("input[name='idItemArray[]']").map(function () { return $(this).attr("data-iditemarray"); }).get();
            var idUnidad  = $("input[name='idItemArray[]']").map(function () { return $(this).attr("data-idunidad"); }).get();
            var cantidad  = $("input[name='cantidadArray[]']").map(function () { return $(this).attr("data-cantidad"); }).get();

            const contenedorArray = [];
            for (var i = 0; i < cantidad.length; i++) {
                contenedorArray.push({
                    infoIdContratoDetalle: idItem[i],
                    infoIdDepartamento:    idUnidad[i],   // unidad de origen de esta línea
                    infoCantidad:          cantidad[i],
                });
            }

            openLoading();
            var formData = new FormData();
            formData.append('id_retiro',       ID_RETIRO);
            formData.append('contenedorArray', JSON.stringify(contenedorArray));

            axios.post(urlAdmin + '/admin/historial/contratos/salidas/extras/guardar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 10) {
                        Swal.fire({
                            title: 'Extras guardados',
                            type: 'success',
                            allowOutsideClick: false,
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Aceptar'
                        }).then((r) => {
                            if (r.value) {
                                $("#matriz tbody tr").remove();
                                window.clavesAgregadas.clear();
                            }
                        });
                    } else if (response.data.success === 2) {
                        toastr.error('Fila #' + response.data.fila + ': supera lo disponible en la unidad (' + response.data.disponible + ')');
                    } else if (response.data.success === 3) {
                        toastr.error(response.data.mensaje);
                    } else if (response.data.success === 4) {
                        toastr.error('Fila #' + response.data.fila + ': el ítem no está asignado a la unidad de origen o coincide con la unidad destino');
                    } else if (response.data.success === 5) {
                        toastr.error('Hay ítems repetidos para la misma unidad');
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al guardar'); });
        }

        function borrarFila(el, clave) {
            if (clave) window.clavesAgregadas.delete(String(clave));
            el.closest('tr').remove();
            setearFila();
        }

        function setearFila() {
            $('#matriz tbody tr').each(function (i) {
                $(this).find('td:first p').text(i + 1);
            });
        }
    </script>
@endsection
