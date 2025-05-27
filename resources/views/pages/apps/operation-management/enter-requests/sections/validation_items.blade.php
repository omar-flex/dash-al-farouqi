<div data-repeater-validations-list>
    @foreach($warehouseItems as $item)
        <div class="row px-3" data-repeater-products-item>
            <input type="hidden" name="items_id[]" value="{{$item->id}}"/>
            <div class="col-2 mb-2">
                <input class="form-control form-control-solid-bg form-control-sm codes" disabled type="text"
                       value="{{$item?->product->name}}"/>
            </div>
            <div class="col-1 mb-2">
                <input class="form-control form-control-solid-bg form-control-sm codes" disabled type="text"
                       value="{{$item?->product?->UnitMeasure?->name}}"/>
            </div>
            <div class="col-1 mb-2">
                <input class="form-control form-control-sm form-control-solid-bg" disabled
                       value="{{$item?->quantity}}"/>
            </div>
            <div class="col-1 mb-2">
                <input class="form-control form-control-sm form-control-solid-bg" disabled
                       value="{{$item?->other_quantity}}"/>
            </div>
            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm"
                       name="custom_tariff_codes[]"
                       placeholder="Customs Tariff Code" value="{{$item?->custom_tariff_code}}"
                       @if($disabled) disabled @endif/>
            </div>
            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="custom_values[]"
                       placeholder="Fixed Cost" value="{{$item?->custom_value}}"
                       @if($disabled) disabled @endif/>
            </div>
            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="gross_weights[]"
                       placeholder="Gross Weight" value="{{$item?->gross_weight}}"
                       @if($disabled) disabled @endif/>
            </div>

            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="net_weights[]"
                       placeholder="Net Weight" value="{{$item?->net_weight}}"
                       @if($disabled) disabled @endif/>
            </div>
        </div>
    @endforeach
</div>


