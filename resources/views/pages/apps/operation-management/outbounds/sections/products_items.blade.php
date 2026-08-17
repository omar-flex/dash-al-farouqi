@php
    $productRows = $warehouseItems->isNotEmpty() ? $warehouseItems : collect([null]);
@endphp

<div id="product_rows">
    <div data-repeater-products-list>
        @foreach($productRows as $outboundItem)
            @php
                $selectedWarehouseItem = $outboundItem?->WarehouseItem;
                $selectedBalanceItem = $outboundItem
                    ? $selectableWarehouseItems->firstWhere('id', $outboundItem->warehouse_item_id)
                    : null;
                $selectedLocation = $selectedWarehouseItem
                    ? collect([
                        $selectedWarehouseItem?->LocationLine?->Location?->Warehouse?->code,
                        $selectedWarehouseItem?->LocationLine?->Location?->code,
                        $selectedWarehouseItem?->LocationLine?->code,
                        $selectedWarehouseItem?->level,
                        $selectedWarehouseItem?->pallet,
                    ])->filter(fn ($value) => $value !== null && $value !== '')->implode('-')
                    : '';
                $selectedAvailable = $selectedBalanceItem
                    ? (float) ($selectedBalanceItem->calculated_available_quantity ?? $selectedBalanceItem->remaining_quantity)
                    : null;
                $selectedOtherAvailable = $selectedBalanceItem
                    ? (float) ($selectedBalanceItem->calculated_available_other_quantity
                        ?? $selectedBalanceItem->remaining_other_quantity
                        ?? 0)
                    : null;
            @endphp

            <div class="row px-3" data-repeater-products-item
                 data-original-warehouse-item-id="{{ $outboundItem?->warehouse_item_id }}"
                 data-original-quantity="{{ $outboundItem?->quantity ?? 0 }}"
                 data-original-other-quantity="{{ $outboundItem?->other_quantity ?? 0 }}">
                <input type="hidden" data-field="id" value="{{ $outboundItem?->id }}"/>

                <div class="col-3 mb-2 search-wrapper">
                    <select data-field="warehouse_item_id"
                            data-control="select2"
                            class="form-select form-select-solid-bg form-select-sm mb-2 warehouse-items"
                            data-placeholder="Product / batch" @disabled($disabled)>
                        <option></option>
                        @foreach($selectableWarehouseItems as $stockItem)
                            @php
                                $stockLocation = collect([
                                    $stockItem?->LocationLine?->Location?->Warehouse?->code,
                                    $stockItem?->LocationLine?->Location?->code,
                                    $stockItem?->LocationLine?->code,
                                    $stockItem?->level,
                                    $stockItem?->pallet,
                                ])->filter(fn ($value) => $value !== null && $value !== '')->implode('-');
                            @endphp
                            <option value="{{ $stockItem->id }}"
                                    data-barcode="{{ $stockItem?->Product?->barcode }}"
                                    data-batch-number="{{ $stockItem->batch_number }}"
                                    data-unit-measure="{{ $stockItem?->Product?->UnitMeasure?->name }}"
                                    data-location="{{ $stockLocation }}"
                                    data-available="{{ $stockItem->calculated_available_quantity ?? $stockItem->remaining_quantity }}"
                                    data-other-available="{{ $stockItem->calculated_available_other_quantity ?? $stockItem->remaining_other_quantity ?? 0 }}"
                                    @selected($outboundItem?->warehouse_item_id === $stockItem->id)>
                                {{ $stockItem?->Product?->name }}
                                @if($stockItem->batch_number !== null && $stockItem->batch_number !== '')
                                    - BN {{ $stockItem->batch_number }}
                                @endif
                                - #{{ $stockItem->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-1 mb-2">
                    <input type="number" min="0.001" step="0.001"
                           class="form-control form-control-sm quantities"
                           data-field="quantity" placeholder="Qty"
                           value="{{ $outboundItem?->quantity }}"
                           @disabled($disabled)
                           @if($selectedAvailable !== null) max="{{ $selectedAvailable }}" @endif/>
                </div>

                <div class="col-1 mb-2">
                    <input type="number" min="0" step="0.001"
                           class="form-control form-control-sm other_quantities"
                           data-field="other_quantity" placeholder="Other Qty"
                           value="{{ $outboundItem?->other_quantity }}"
                           @disabled($disabled)
                           @if($selectedOtherAvailable !== null) max="{{ $selectedOtherAvailable }}" @endif/>
                </div>

                <div class="col mb-2">
                    <input class="form-control form-control-solid-bg form-control-sm barcode"
                           placeholder="Barcode" type="text" readonly
                           value="{{ $selectedWarehouseItem?->Product?->barcode }}"/>
                </div>

                <div class="col mb-2">
                    <input class="form-control form-control-solid-bg form-control-sm batch_number"
                           placeholder="BN" type="text" readonly
                           value="{{ $selectedWarehouseItem?->batch_number }}"/>
                </div>

                <div class="col mb-2">
                    <input class="form-control form-control-solid-bg form-control-sm unit_measure"
                           placeholder="UoM" type="text" readonly
                           value="{{ $selectedWarehouseItem?->Product?->UnitMeasure?->name }}"/>
                </div>

                <div class="col-md-2 mb-2">
                    <input type="text" class="form-control form-control-sm form-control-solid-bg location"
                           readonly placeholder="WH-H-L" value="{{ $selectedLocation }}"/>
                </div>

                <div class="col-md-2 mb-2">
                    <select data-field="outbound_car_id" data-control="select2"
                            class="form-select form-select-solid-bg form-select-sm mb-2 cars"
                            data-placeholder="Car" @disabled($disabled)>
                        <option></option>
                        @foreach($cars as $car)
                            <option value="{{ $car->id }}" @selected($outboundItem?->outbound_car_id === $car->id)>
                                {{ $car->number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(!$disabled)
                    <div class="col mb-2">
                        <button type="button" data-repeater-products-delete
                                class="btn btn-icon btn-light-danger btn-sm">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<style>
    .span_error {
        font-size: 10px;
    }
</style>
