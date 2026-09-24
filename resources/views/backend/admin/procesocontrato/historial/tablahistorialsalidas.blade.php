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
                                    <th style="width: 8%">Código</th>
                                    <th style="width: 16%">Contrato</th>
                                    <th style="width: 12%">Proveedor</th>
                                    <th style="width: 8%">Fecha Registro</th>
                                    <th style="width: 8%">Fecha Factura</th>
                                    <th style="width: 9%">No. Factura</th>
                                    <th style="width: 15%">Descripción</th>
                                    <th style="width: 6%">Estado</th>
                                    <th style="width: 18%">Opciones</th>
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
                                        <td data-order="{{ $dato->fecha_factura ?? '' }}">{{ $dato->fecha_factura_fmt ?? '' }}</td>
                                        <td>{{ $dato->no_factura ?? '' }}</td>
                                        <td>{{ $dato->descripcion ?? '' }}</td>
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
                                                    onclick="verDetalle({{ $dato->id }}, '{{ addslashes($dato->contrato->nombre_proceso ?? '') }}', '{{ $dato->fecha_fmt }}', {{ $cerrado ? 1 : 0 }})">
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
