<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        @if($arrayRetiros->isEmpty())
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0">No se encontraron retiros con los filtros seleccionados.</p>
                            </div>
                        @else
                            <table id="tabla" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th style="width: 6%">Código</th>
                                    <th style="width: 13%">Contrato</th>
                                    <th style="width: 10%">Proveedor</th>
                                    <th style="width: 7%">Fecha Registro</th>
                                    <th style="width: 9%">Factura</th>
                                    <th style="width: 12%">Unidad de Origen</th>
                                    <th style="width: 9%">Destino</th>
                                    <th style="width: 11%">Descripción</th>
                                    <th style="width: 7%">Total</th>
                                    <th style="width: 5%">Estado</th>
                                    <th style="width: 11%">Opciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($arrayRetiros as $dato)
                                    @php $cerrado = $dato->contrato && $dato->contrato->estado == 'finalizado'; @endphp
                                    <tr>
                                        <td>{{ $dato->contrato->codigo ?? '' }}</td>
                                        <td>{{ $dato->contrato->nombre_proceso ?? '' }}</td>
                                        <td>{{ $dato->contrato->proveedor->nombre ?? '' }}</td>
                                        <td data-order="{{ $dato->fecha }}">{{ $dato->fecha_fmt }}</td>
                                        <td data-order="{{ $dato->fecha_factura ?? '' }}">
                                            {{ $dato->no_factura ?? '' }}
                                            @if($dato->fecha_factura_fmt)
                                                <br><small class="text-muted">{{ $dato->fecha_factura_fmt }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @foreach($dato->origenes as $nombreOrigen)
                                                <span class="badge badge-primary" style="font-size: 11px; margin: 1px 0">{{ $nombreOrigen }}</span>
                                            @endforeach
                                            @if($dato->sin_origen)
                                                <span class="badge badge-secondary" style="font-size: 11px; margin: 1px 0">Sin unidad</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($dato->otra_unidad)
                                                <span class="badge badge-warning" style="font-size: 11px">Otra unidad</span>
                                                <br>{{ $dato->departamentoDestino->nombre ?? '' }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $dato->descripcion ?? '' }}</td>
                                        <td class="text-right" data-order="{{ $dato->total_num }}">{{ $dato->total_fmt }}</td>
                                        <td class="text-center">
                                            @if($cerrado)
                                                <span class="badge badge-danger">Finalizado</span>
                                            @else
                                                <span class="badge badge-success">Vigente</span>
                                            @endif
                                        </td>
                                        <td class="text-center">

                                            <button type="button"
                                                    style="margin: 3px"
                                                    class="btn btn-secondary btn-xs"
                                                    onclick="generarPdf({{ $dato->id }})">
                                                <i class="fas fa-file-pdf"></i> PDF
                                            </button>

                                            @if(!$cerrado)
                                                <button type="button"
                                                        class="btn btn-success btn-xs"
                                                        onclick="window.location.href='{{ url('/admin/historial/contratos/salidas/extras') }}/' + {{ $dato->id }}">
                                                    <i class="fas fa-plus"></i> Extras
                                                </button>
                                            @endif

                                            <button type="button"
                                                    style="margin: 3px"
                                                    class="btn btn-info btn-xs"
                                                    data-contrato="{{ $dato->contrato->nombre_proceso ?? '' }}"
                                                    data-fecha="{{ $dato->fecha_fmt }}"
                                                    data-cerrado="{{ $cerrado ? 1 : 0 }}"
                                                    onclick="verDetalle({{ $dato->id }}, this)">
                                                <i class="fas fa-list"></i> Detalle
                                            </button>

                                            @if(!$cerrado)
                                                <button type="button"
                                                        style="margin: 3px"
                                                        class="btn btn-warning btn-xs"
                                                        onclick="modalEditar({{ $dato->id }})">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <button type="button"
                                                        style="margin: 3px"
                                                        class="btn btn-danger btn-xs"
                                                        onclick="eliminar({{ $dato->id }})">
                                                    <i class="fas fa-trash"></i> Borrar
                                                </button>
                                            @endif

                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $('[data-toggle="tooltip"]').tooltip();
</script>
