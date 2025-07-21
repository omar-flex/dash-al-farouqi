@php
    $total_qty = $warehouseItems->sum('quantity');
    $total_other_qty = $warehouseItems->sum('other_quantity');
    $total_remaining_qty = $warehouseItems->sum(fn($item) => $item->SumOutboundItems() - $item->quantity);
    $total_remaining_other_qty = $warehouseItems->sum(fn($item) => $item->SumOutboundItemsOtherQuantity() - $item->other_quantity);
    $total_custom_value = $warehouseItems->sum('custom_value');
    $total_gross_weight = $warehouseItems->sum('gross_weight');
    $total_net_weight = $warehouseItems->sum('net_weight');
@endphp
<div class="card">
    <div class="card-header">
        <div class="card-title"> Product Items</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-rounded table-bordered text-center table-striped">
                <thead>
                <tr class="fw-semibold fs-7 py-4">
                    <th class="text-primary">Product</th>
                    <th class="text-primary">UoM</th>
                    <th class="text-primary">Qty</th>
                    <th class="text-primary">Remaining Qty</th>
                    <th class="text-primary">Other Qty</th>
                    <th class="text-primary">Remaining Other Qty</th>
                    <th class="text-primary">Customs Tariff</th>
                    <th class="text-primary">Customs Value</th>
                    <th class="text-primary">Gross Weight</th>
                    <th class="text-primary">Net Weight</th>
                </tr>
                </thead>
                <tbody>
                @foreach($warehouseItems as $item)
                    <tr class="py-5 fw-semibold  border-bottom border-gray-300 fs-7 ">
                        <td class="fs-8">{{$item?->product->name}}</td>
                        <td>{{$item?->product?->UnitMeasure?->name}}</td>
                        <td>{{$item?->quantity}}</td>
                        <td>{{$item?->SumOutboundItems() - $item?->quantity}}</td>
                        <td class="text-muted">{{$item?->other_quantity ?? 0}}</td>
                        <td class="text-muted">{{$item?->SumOutboundItemsOtherQuantity() - $item?->other_quantity}}</td>
                        <td>{{$item?->custom_tariff_code}}</td>
                        <td>{{$item?->custom_value}}</td>
                        <td>{{$item?->gross_weight}}</td>
                        <td>{{$item?->net_weight}}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="fw-bold fs-8">
                    <td colspan="2" class="text-danger">Total</td>
                    <td class="text-danger">{{ number_format($total_qty,4) }}</td>
                    <td class="text-danger">{{ number_format($total_remaining_qty,4) }}</td>
                    <td class="text-danger">{{ number_format($total_other_qty,4) }}</td>
                    <td class="text-danger">{{ number_format($total_remaining_other_qty,4) }}</td>
                    <td class="text-danger">---</td>
                    <td class="text-danger">{{ number_format($total_custom_value,4) }}</td>
                    <td class="text-danger">{{ number_format($total_gross_weight,4) }}</td>
                    <td class="text-danger">{{ number_format($total_net_weight,4) }}</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
