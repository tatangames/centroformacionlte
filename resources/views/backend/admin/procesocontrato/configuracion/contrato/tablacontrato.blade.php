<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table id="tabla" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width: 15%">Código</th>
                                <th style="width: 25%">Proceso</th>
                                <th style="width: 20%">Proveedor</th>
                                <th style="width: 10%">Fecha Inicio</th>
                                <th style="width: 10%">Fecha Fin</th>
                                <th style="width: 8%">Estado</th>
                                <th style="width: 12%">Opciones</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($lista as $dato)
                                <tr>
                                    <td>{{ $dato->codigo_texto }}</td>
                                    <td>{{ $dato->nombre_proceso }}</td>
                                    <td>{{ $dato->proveedor_nombre }}</td>
                                    <td data-order="{{ $dato->fecha_inicio_orden }}">{{ $dato->fecha_inicio_texto }}</td>
                                    <td data-order="{{ $dato->fecha_fin_orden }}">{{ $dato->fecha_fin_texto }}</td>
                                    <td><span class="badge {{ $dato->estado_clase }}">{{ $dato->estado_texto }}</span></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-xs" onclick="informacion({{ $dato->id }})">
                                            <i class="fas fa-edit" title="Editar"></i>&nbsp; Editar
                                        </button>
                                        <a style="margin: 5px" href="{{ url('/admin/contrato/detalle/'.$dato->id) }}" class="btn btn-info btn-xs">
                                            <i class="fas fa-list" title="Datos del Contrato"></i>&nbsp; Datos del Contrato
                                        </a>
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
