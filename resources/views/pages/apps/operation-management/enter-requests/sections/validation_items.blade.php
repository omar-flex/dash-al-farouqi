<div data-repeater-validations-list>
    @foreach($warehouseItems as $item)
        <div class="row px-3" data-repeater-products-item>
            <input type="hidden" name="items_id[]" value="{{$item->id}}"/>
            <div class="col-2 mb-2">
                <input class="form-control form-control-solid-bg form-control-sm codes" disabled type="text" value="{{$item?->product->name}}"/>
            </div>
            <div class="col-2 mb-2">
                <input class="form-control form-control-solid-bg form-control-sm codes" disabled type="text" value="{{$item?->product?->barcode}}"/>
            </div>
            <div class="col-1 mb-2">
                <input class="form-control form-control-solid-bg form-control-sm codes" disabled placeholder="BN" value="{{$item?->batch_number}}"/>
            </div>
            <div class="col-1 mb-2">
                <input class="form-control form-control-solid-bg form-control-sm codes" disabled type="text" value="{{$item?->product?->UnitMeasure?->name}}"/>
            </div>
            <div class="col-1 mb-2">
                <input  class="form-control form-control-sm form-control-solid-bg" disabled value="{{$item?->quantity}}"/>
            </div>

            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="fixed_costs[]"
                       placeholder="Fixed Cost" value="{{$item?->fixed_cost}}"  @if($enterRequest->status_id == \App\Models\EnterRequestStatus::AUTHORIZATION) disabled @endif/>
            </div>
            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="gross_weights[]"
                       placeholder="Gross Weight" value="{{$item?->gross_weight}}" @if($enterRequest->status_id == \App\Models\EnterRequestStatus::AUTHORIZATION) disabled @endif/>
            </div>

            <div class="col mb-2">
                <input type="number" step="any" min="0" class="form-control form-control-sm" name="net_weights[]"
                       placeholder="Net Weight" value="{{$item?->net_weight}}" @if($enterRequest->status_id == \App\Models\EnterRequestStatus::AUTHORIZATION) disabled @endif/>
            </div>
        </div>
    @endforeach
</div>


