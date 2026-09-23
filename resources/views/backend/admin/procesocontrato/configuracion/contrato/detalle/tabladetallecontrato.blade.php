<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table id="tabla" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width: 30%">Nombre</th>
                                <th style="width: 10%">U/M</th>
                                <th style="width: 15%">Cantidad</th>
                                <th style="width: 15%">Precio</th>
                                <th style="width: 20%">Subtotal</th>
                                <th style="width: 20%">Opciones</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($lista as $dato)
                                <tr>
                                    <td>{{ $dato->nombre }}</td>
                                    <td>{{ $dato->unidadMedida->nombre ?? '' }}</td>
                                    <td>{{ $dato->cantidad }}</td>
                                    <td>{{ $dato->precioFormat }}</td>
                                    <td>{{ $dato->subtotalFormat }}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-xs" onclick="informacion({{ $dato->id }})">
                                            <i class="fas fa-edit" title="Editar"></i>&nbsp; Editar
                                        </button>
                                        <button type="button" class="btn btn-danger btn-xs" onclick="eliminar({{ $dato->id }})">
                                            <i class="fas fa-trash" title="Eliminar"></i>&nbsp; Borrar
                                        </button>
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
