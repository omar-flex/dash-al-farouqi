<div id="product_items">
    <div data-repeater-products-list>
        @if(count($warehouseItems) == 0)
            <div class="row px-3" data-repeater-products-item>
                <div class="col-2 mb-2 search-wrapper">
                    <input type="text" name="products[]"
                           class="form-control form-control-solid-bg form-control-sm searchInput"
                           autocomplete="off" placeholder="search...">
                    <div class="suggestions list-group"></div>
                </div>
                <div class="col mb-2">
                    <input class="form-control form-control-solid-bg form-control-sm codes"
                           placeholder="Barcode" type="text" name="barcodes[]"
                           value="{{time() * 1000 + rand(0, 999)}}"/>
                </div>
                <div class="col mb-2">
                    <input class="form-control form-control-solid-bg form-control-sm"
                           placeholder="BN" type="text" name="batch_numbers[]"/>
                </div>
                <div class="col mb-2">
                    <select name="unit_measures[]"
                            data-control="select2"
                            class="form-select form-select-solid-bg form-select-sm mb-2 unit_measures"
                            data-placeholder="UoM ">
                        <option></option>
                        @foreach($payload->unitMeasures as $unitMeasure)
                            <option value="{{ $unitMeasure->id }}">
                                {{ $unitMeasure->name}}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-1 mb-2">
                    <input type="number" min="1" class="form-control form-control-sm form-control-solid-bg"
                           name="quantities[]"
                           aria-describedby="capacities" placeholder="Qty"/>
                </div>
                <div class="col-1 mb-2">
                    <input type="number" min="1" class="form-control form-control-sm form-control-solid-bg"
                           name="other_quantities[]"
                           aria-describedby="capacities" placeholder="PCS"/>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="locations[]"
                            class="form-select form-select-solid-bg form-select-sm mb-2 locations"
                            data-control="select2"
                            data-placeholder="WH-H-L">
                        <option></option>
                        @foreach($payload->locations as $location)
                            <option
                                value="{{ $location->id }}"> {{ $location->code}} </option>
                        @endforeach
                    </select>
                </div>
                <div class="col mb-2">
                    <input type="text" class="form-control form-control-sm" name="levels[]"
                           placeholder="Level"/>
                </div>
                <div class="col mb-2">
                    <input type="text" class="form-control form-control-sm" name="pallets[]"
                           placeholder="Pallet"/>
                </div>
                <div class="col mb-2">
                    <button type="button" data-repeater-products-delete
                            class="btn btn-icon btn-light-danger btn-sm">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        @else
            @foreach($warehouseItems as $item)
                <div class="row px-3" data-repeater-products-item>
                    <input type="hidden" name="items_id[]" value="{{$item->id}}"/>
                    <div class="col-2 mb-2 search-wrapper">
                        <input type="text" name="products[]"
                               class="form-control form-control-solid-bg form-control-sm searchInput"
                               autocomplete="off" placeholder="search..."
                               value="{{$item?->product?->name}}" {{$disabled}}>
                        <div class="suggestions list-group"></div>
                    </div>
                    <div class="col mb-2">
                        <input class="form-control form-control-solid-bg form-control-sm codes"
                               placeholder="Barcode" type="text" name="barcodes[]"
                               value="{{$item?->product?->barcode}}"
                               {{$disabled}} title="{{$item?->product?->barcode}}"/>
                    </div>
                    <div class="col mb-2">
                        <input class="form-control form-control-solid-bg form-control-sm"
                               placeholder="BN" type="text" name="batch_numbers[]"
                               value="{{$item?->batch_number}}" title="{{$item?->batch_number}}" {{$disabled}}
                        />
                    </div>
                    <div class="col mb-2">
                        <select name="unit_measures[]"
                                data-control="select2"
                                class="form-select form-select-solid-bg form-select-sm mb-2 unit_measures"
                                data-placeholder="UoM " {{$disabled}}>
                            <option></option>
                            @foreach($payload->unitMeasures as $unitMeasure)
                                <option value="{{ $unitMeasure->id }}"
                                        @if($item?->product?->unit_measure_id == $unitMeasure->id) selected @endif>
                                    {{ $unitMeasure->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-1 mb-2">
                        <input type="number" min="1"
                               class="form-control form-control-sm form-control-solid-bg"
                               name="quantities[]" id="quantities"
                               aria-describedby="capacities" placeholder="Qty"
                               value="{{$item?->quantity}}" {{$disabled}} title="{{$item?->quantity}}"/>
                    </div>
                    <div class="col-1 mb-2">
                        <input type="number" min="1" class="form-control form-control-sm form-control-solid-bg"
                               name="other_quantities[]"
                               aria-describedby="capacities" placeholder="PCS"
                               value="{{$item?->other_quantity}}" {{$disabled}} title="{{$item?->other_quantity}}"/>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="locations[]"
                                class="form-select form-select-solid-bg form-select-sm mb-2 locations"
                                data-control="select2"
                                data-placeholder="WH-H-L" {{$disabled}}>
                            <option></option>
                            @foreach($payload->locations as $location)
                                <option @if($item->location_line_id == $location->id) selected @endif
                                value="{{$location->id }}">
                                    {{ $location->code}} </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col mb-2">
                        <input type="text" class="form-control form-control-sm" name="levels[]"
                               placeholder="Level" value="{{$item?->level}}" {{$disabled}} title="{{$item?->level}}"/>
                    </div>
                    <div class="col mb-2">
                        <input type="text" class="form-control form-control-sm" name="pallets[]"
                               placeholder="Pallet" value="{{$item?->pallet}}"
                               {{$disabled}} title="{{$item?->pallet}}"/>
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
        @endif
    </div>
</div>

