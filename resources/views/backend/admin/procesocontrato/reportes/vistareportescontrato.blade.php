{{-- resources/views/backend/reportes/reportecontratos.blade.php --}}
@extends('adminlte::page')
@section('title', 'Reportes de Contratos')
@section('plugins.Sweetalert2', true)
@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">
    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">{{ Auth::guard('admin')->user()->nombre }}</span>
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
        *:focus { outline: none; }
        .reporte-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 18px rgba(0,0,0,.10);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .reporte-header {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #0b2e5c, #1f6ea6);
        }
        .reporte-header i { font-size: 22px; color: #fff; }
        .reporte-header h5 {
            color: #fff; font-size: 14px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em; margin: 0;
        }
        .reporte-body { padding: 22px 24px; background: #fff; }
        .field-label {
            font-size: 11px; font-weight: 700; color: #6b7a99;
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 6px; display: block;
        }
        .btn-pdf {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 20px; border-radius: 8px; font-weight: 600;
            font-size: 13px; border: none; cursor: pointer;
            transition: all .2s; margin-top: 14px;
            background: linear-gradient(135deg, #0b2e5c, #1f6ea6);
            color: #fff; box-shadow: 0 4px 14px rgba(31,110,166,.35);
        }
        .btn-pdf:hover { transform: translateY(-1px); filter: brightness(1.08); color: #fff; }
        .divider { border: none; border-top: 2px dashed #e8eef8; margin: 10px 0 20px 0; }

        /* Tabs de tipo de reporte */
        .tipo-tabs { display: flex; gap: 8px; margin-bottom: 4px; flex-wrap: wrap; }
        .tipo-tab {
            flex: 1; min-width: 150px; text-align: center; padding: 12px 10px;
            border-radius: 8px; border: 2px solid #e8eef8; background: #f8fafc;
            cursor: pointer; font-size: 12px; font-weight: 700; color: #6b7a99;
            transition: all .15s; user-select: none;
        }
        .tipo-tab i { display: block; font-size: 18px; margin-bottom: 4px; }
        .tipo-tab.active { border-color: #1f6ea6; background: #e6f0f9; color: #0b4c85; }
        .modo-hint {
            margin-top: 8px; font-size: 11px; font-weight: 600;
            padding: 6px 10px; border-radius: 6px; display: block;
            background: #e6f0f9; color: #0b4c85;
        }
        .campo-condicional { display: none; }
        .campo-condicional.mostrar { display: block; }
    </style>

    {{-- Formulario oculto para POST (se reutiliza, cambiando la action según el tipo) --}}
    <form id="form-pdf" method="POST" target="_blank">
        @csrf
        <input type="hidden" name="idcontrato"        id="h-idcontrato">
        <input type="hidden" name="estado"             id="h-estado">
        <input type="hidden" name="desde"              id="h-desde">
        <input type="hidden" name="hasta"               id="h-hasta">
    </form>

    <div id="divcontenedor">
        <section class="content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="reporte-card">
                            <div class="reporte-header">
                                <i class="fas fa-file-contract"></i>
                                <h5>Reportes de Contratos</h5>
                            </div>
                            <div class="reporte-body">

                                {{-- ── Selector de tipo de reporte ── --}}
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-list-alt mr-1"></i>Tipo de Reporte
                                    </label>
                                    <div class="tipo-tabs">
                                        <div class="tipo-tab active" data-tipo="saldos" id="tab-saldos">
                                            <i class="fas fa-balance-scale"></i>Saldos y Disponibilidad
                                        </div>
                                        <div class="tipo-tab" data-tipo="periodo" id="tab-periodo">
                                            <i class="fas fa-exchange-alt"></i>Movimientos por Período
                                        </div>
                                        <div class="tipo-tab" data-tipo="general" id="tab-general">
                                            <i class="fas fa-th-list"></i>Estado General
                                        </div>
                                    </div>
                                    <span id="modo-hint" class="modo-hint">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <span id="modo-hint-texto">
                                            Muestra, por cada ítem de un contrato específico, lo contratado, lo retirado y lo disponible.
                                        </span>
                                    </span>
                                </div>

                                <hr class="divider">

                                {{-- ── Contrato (saldos y periodo) ── --}}
                                <div class="form-group campo-condicional mostrar" id="campo-contrato">
                                    <label class="field-label">
                                        <i class="fas fa-file-contract mr-1"></i>Contrato
                                    </label>
                                    <select class="form-control" id="sel-contrato">
                                        <option value="">— Selecciona un contrato —</option>
                                        @foreach($contratos as $c)
                                            <option value="{{ $c->id }}"
                                                    data-cerrado="{{ $c->estado == 'finalizado' ? '1' : '0' }}">
                                                {{ $c->codigo ? $c->codigo . ' — ' : '' }}{{ $c->nombre_proceso }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- ── Rango de fechas (solo periodo) ── --}}
                                <div class="row campo-condicional" id="campo-fechas">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="field-label">
                                                <i class="fas fa-calendar-alt mr-1"></i>Fecha Desde
                                            </label>
                                            <input type="date" class="form-control" id="inp-desde">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="field-label">
                                                <i class="fas fa-calendar-check mr-1"></i>Fecha Hasta
                                            </label>
                                            <input type="date" class="form-control" id="inp-hasta">
                                        </div>
                                    </div>
                                </div>

                                {{-- ── Estado del contrato (solo general) ── --}}
                                <div class="form-group campo-condicional" id="campo-estado-general">
                                    <label class="field-label">
                                        <i class="fas fa-toggle-on mr-1"></i>Filtrar por Estado
                                    </label>
                                    <select class="form-control" id="sel-estado-general">
                                        <option value="todos">— Todos —</option>
                                        <option value="vigente">Solo Vigentes</option>
                                        <option value="finalizado">Solo Finalizados</option>
                                    </select>
                                </div>

                                <button type="button" onclick="generarPDF()" class="btn-pdf">
                                    <img src="{{ asset('images/logopdf.png') }}" width="22px" height="22px">
                                    Generar PDF
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script>
        var tipoActual = 'saldos';

        const RUTAS_PDF = {
            saldos:   "{{ url('admin/reporte/contratos/saldos/pdf') }}",
            periodo:  "{{ url('admin/reporte/contratos/periodo/pdf') }}",
            general:  "{{ url('admin/reporte/contratos/general/pdf') }}",
        };

        const HINTS = {
            saldos:  'Muestra, por cada ítem de un contrato específico, lo contratado, lo retirado y lo disponible.',
            periodo: 'Las fechas que busca es por Fecha de Registro',
            general: 'Muestra un listado de todos los contratos con su porcentaje de ejecución.',
        };

        function formatContrato(option) {
            if (!option.id) return option.text;
            var cerrado = $(option.element).data('cerrado');
            if (parseInt(cerrado) === 1) {
                return $('<span>' + option.text +
                    ' <span style="background:#dc3545;color:#fff;font-size:10px;font-weight:700;' +
                    'padding:2px 7px;border-radius:4px;margin-left:6px;">FINALIZADO</span></span>');
            }
            return $('<span>' + option.text + '</span>');
        }

        $(document).ready(function () {
            document.getElementById("divcontenedor").style.display = "block";

            $('#sel-contrato').select2({
                theme: "bootstrap-5",
                templateResult: formatContrato,
                templateSelection: formatContrato,
                escapeMarkup: function (markup) { return markup; },
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });

            $('.tipo-tab').on('click', function () {
                cambiarTipo($(this).data('tipo'));
            });
        });

        function cambiarTipo(tipo) {
            tipoActual = tipo;
            $('.tipo-tab').removeClass('active');
            $('#tab-' + tipo).addClass('active');
            $('#modo-hint-texto').text(HINTS[tipo]);

            $('#campo-contrato').toggleClass('mostrar', tipo === 'saldos' || tipo === 'periodo');
            $('#campo-fechas').toggleClass('mostrar', tipo === 'periodo');
            $('#campo-estado-general').toggleClass('mostrar', tipo === 'general');
        }

        function generarPDF() {
            var idcontrato = $('#sel-contrato').val();
            var desde      = $('#inp-desde').val();
            var hasta      = $('#inp-hasta').val();
            var estado     = $('#sel-estado-general').val();

            if ((tipoActual === 'saldos' || tipoActual === 'periodo') && !idcontrato) {
                toastr.error('Seleccione un contrato'); return;
            }
            if (tipoActual === 'periodo') {
                if (!desde) { toastr.error('Seleccione la fecha "Desde"'); return; }
                if (!hasta) { toastr.error('Seleccione la fecha "Hasta"'); return; }
                if (desde > hasta) { toastr.error('La fecha "Desde" no puede ser mayor que "Hasta"'); return; }
            }

            $('#h-idcontrato').val(idcontrato || '');
            $('#h-desde').val(desde || '');
            $('#h-hasta').val(hasta || '');
            $('#h-estado').val(estado || 'todos');

            $('#form-pdf').attr('action', RUTAS_PDF[tipoActual]);
            $('#form-pdf').submit();
        }
    </script>
@endsection
