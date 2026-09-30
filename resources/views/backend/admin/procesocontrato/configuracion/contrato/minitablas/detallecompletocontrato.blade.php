<div class="row">
    <div class="col-md-12">
        <h5 class="mb-3">{{ $detalle->nombre }}</h5>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <p class="mb-1 text-muted">Unidad de Medida</p>
        <p><b>{{ $detalle->unidadMedida->nombre ?? '-' }}</b></p>
    </div>
    <div class="col-md-4">
        <p class="mb-1 text-muted">Precio</p>
        <p><b>{{ $precioFormat }}</b></p>
    </div>
    <div class="col-md-4">
        <p class="mb-1 text-muted">Subtotal</p>
        <p><b>{{ $subtotalFormat }}</b></p>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <p class="mb-1 text-muted">Cantidad total</p>
        <p><b>{{ number_format($detalle->cantidad) }}</b></p>
    </div>
    <div class="col-md-4">
        <p class="mb-1 text-muted">Retirado</p>
        <p><b style="color: #dc3545">{{ number_format($retirado) }}</b></p>
    </div>
    <div class="col-md-4">
        <p class="mb-1 text-muted">Disponible</p>
        <p><b style="color: #28a745">{{ number_format($disponible) }}</b></p>
    </div>
</div>

<hr>

<p class="mb-2 text-muted">Unidades asignadas</p>

<table class="table table-bordered table-sm">
    <thead>
    <tr>
        <th>Unidad</th>
        <th style="width: 180px">Cantidad asignada</th>
    </tr>
    </thead>
    <tbody>
    @forelse($detalle->departamentos as $dep)
        <tr>
            <td>{{ $dep->nombre }}</td>
            <td>{{ number_format($dep->pivot->cantidad) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="2" class="text-center text-muted">Sin asignar</td>
        </tr>
    @endforelse
    </tbody>
</table>
