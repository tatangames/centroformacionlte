<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table id="tabla" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width: 19%">Nombre</th>
                                <th style="width: 6%">U/M</th>
                                <th style="width: 21%">Unidades Asignadas</th>
                                <th style="width: 8%">Cantidad</th>
                                <th style="width: 8%">Retirado</th>
                                <th style="width: 8%">Stock Actual</th>
                                <th style="width: 9%">Precio</th>
                                <th style="width: 10%">Subtotal</th>
                                <th style="width: 11%">Opciones</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($lista as $dato)
                                <tr>
                                    <td>{{ $dato->nombre }}</td>
                                    <td>{{ $dato->unidadMedida->nombre ?? '' }}</td>
                                    <td>
                                        @forelse($dato->departamentos as $dep)
                                            <span class="badge badge-info" style="font-size: 12px; margin: 1px 0">
                                                {{ $dep->nombre }}: {{ number_format($dep->pivot->cantidad) }}
                                            </span><br>
                                        @empty
                                            <span class="text-muted">Sin asignar</span>
                                        @endforelse
                                    </td>
                                    <td>{{ number_format($dato->cantidad) }}</td>
                                    <td>{{ number_format($dato->retirado) }}</td>
                                    <td>
                                        @if($dato->stock <= 0)
                                            <span class="badge badge-danger" style="font-size: 12px">{{ number_format($dato->stock) }}</span>
                                        @else
                                            <span class="badge badge-success" style="font-size: 12px">{{ number_format($dato->stock) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $dato->precioFormat }}</td>
                                    <td>{{ $dato->subtotalFormat }}</td>
                                    <td>
                                        @if($dato->retiros_count == 0)
                                            <button type="button" class="btn btn-primary btn-xs" onclick="informacion({{ $dato->id }})">
                                                <i class="fas fa-edit" title="Editar"></i>&nbsp; Editar
                                            </button>
                                            <button type="button" class="btn btn-danger btn-xs" onclick="eliminar({{ $dato->id }})">
                                                <i class="fas fa-trash" title="Eliminar"></i>&nbsp; Borrar
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-secondary btn-xs" onclick="verDetalle({{ $dato->id }})">
                                                <i class="fas fa-eye" title="Detalle"></i>&nbsp; Detalle
                                            </button>
                                            <button type="button" class="btn btn-info btn-xs" onclick="verSalidas({{ $dato->id }})">
                                                <i class="fas fa-list" title="Salidas"></i>&nbsp; Salidas
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
