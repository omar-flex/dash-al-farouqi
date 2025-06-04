<div data-repeater-validations-list>
    @foreach($warehouseItems as $item)
        <div class="row px-3" data-repeater-products-item>
            <input type="hidden" name="items_id[]" value="{{$item->id}}"/>
            <div class="col-2 mb-2">
                <input class="form-control form-control-solid-bg form-control-sm codes" disabled type="text"
                       value="{{$item?->warehouseItem?->Product?->name .' - '. $item?->warehouseItem?->batch_number}}"
                       title="{{$item?->warehouseItem?->Product?->name .' - '. $item?->warehouseItem?->batch_number}}"/>
            </div>
            <div class="col-1 mb-2">
                <input class="form-control form-control-solid-bg form-control-sm codes"
                       disabled type="text" value="{{$item?->warehouseItem?->Product?->UnitMeasure?->name}}"
                       title="{{$item?->warehouseItem?->Product?->UnitMeasure?->name}}"/>
            </div>
            <div class="col-1 mb-2">
                <input class="form-control form-control-sm form-control-solid-bg" disabled value="{{$item?->quantity}}"
                       title="{{$item?->quantity}}"/>
            </div>
            <div class="col-1 mb-2">
                <input class="form-control form-control-sm form-control-solid-bg" disabled value="{{$item?->other_quantity}}"
                       title="{{$item?->other_quantity}}"/>
            </div>
            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm"
                       disabled
                       placeholder="Customs Tariff Code" value="{{$item?->warehouseItem?->custom_tariff_code}}"
                       title="{{$item?->warehouseItem?->custom_tariff_code}}"
                       @if($disabled) disabled @endif/>
            </div>
            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="custom_values[]"
                       placeholder="Fixed Cost" value="{{$item?->custom_value}}" title="{{$item?->custom_value}}"
                       @if($disabled) disabled @endif/>
            </div>
            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="gross_weights[]"
                       placeholder="Gross Weight" value="{{$item?->gross_weight}}" title="{{$item?->gross_weight}}"
                       @if($disabled) disabled @endif/>
            </div>

            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="net_weights[]"
                       placeholder="Net Weight" value="{{$item?->net_weight}}" title="{{$item?->net_weight}}"
                       @if($disabled) disabled @endif/>
            </div>
        </div>
    @endforeach
</div>


