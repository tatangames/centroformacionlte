<div class="row mb-3">
    <div class="col-md-12">
        <h5 class="mb-1">{{ $detalle->nombre }}</h5>
        <span class="text-muted">
            U/M: {{ $detalle->unidadMedida->nombre ?? '' }}
            &nbsp;|&nbsp; Unidad Asignada: {{ $detalle->departamento->nombre ?? '' }}
        </span>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="info-box mb-0">
            <div class="info-box-content">
                <span class="info-box-text">Cantidad Contratada</span>
                <span class="info-box-number">{{ $detalle->cantidad }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box mb-0">
            <div class="info-box-content">
                <span class="info-box-text">Total Retirado</span>
                <span class="info-box-number">{{ $totalRetirado }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box mb-0">
            <div class="info-box-content">
                <span class="info-box-text">Disponible</span>
                <span class="info-box-number {{ $disponible < 0 ? 'text-danger' : 'text-success' }}">{{ $disponible }}</span>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm">
        <thead>
        <tr>
            <th>Fecha</th>
            <th>No. Factura</th>
            <th>Fecha Factura</th>
            <th>Descripción</th>
            <th class="text-right">Cantidad</th>
        </tr>
        </thead>
        <tbody>
        @forelse($lista as $dato)
            <tr>
                <td>{{ $dato->fechaFormat }}</td>
                <td>{{ $dato->retiroContrato->no_factura ?? '' }}</td>
                <td>{{ $dato->fechaFacturaFormat }}</td>
                <td>{{ $dato->retiroContrato->descripcion ?? '' }}</td>
                <td class="text-right">{{ $dato->cantidad }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted">Este item no tiene salidas registradas</td>
            </tr>
        @endforelse
        </tbody>
        @if($lista->count() > 0)
            <tfoot>
            <tr>
                <th colspan="4" class="text-right">Total</th>
                <th class="text-right">{{ $totalRetirado }}</th>
            </tr>
            </tfoot>
        @endif
    </table>
</div>
