<div id="product_items">
    <div data-repeater-products-list>
        @if(count($warehouseItems) == 0)
            <div class="row px-3" data-repeater-products-item>
                <input type="hidden" name="warehouse_item_ids[]" class="warehouse_item_ids"/>
                <div class="col-3 mb-2 search-wrapper">
                    <select name="products_id[]"
                            data-control="select2"
                            class="form-select form-select-solid-bg form-select-sm mb-2 products"
                            data-placeholder="Product">
                        <option></option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" data-batch-number = "{{ $product->batch_number }}">
                                @if($product->batch_number)
                                    {{ $product->name .' - '. $product->batch_number}}
                                @else
                                    {{ $product->name}}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-1 mb-2">
                    <input type="number" min="1" class="form-control form-control-sm quantities"
                           name="quantities[]" step="any"
                           aria-describedby="capacities" placeholder="Qty"/>
                </div>
                <div class="col-1 mb-2">
                    <input type="number" min="1" class="form-control form-control-sm other_quantities"
                           name="other_quantities[]"
                           aria-describedby="capacities" placeholder="Other Qty"/>
                </div>
                <div class="col mb-2">
                    <input class="form-control form-control-solid-bg form-control-sm barcode"
                           placeholder="Barcode" type="text" disabled/>
                </div>
                <div class="col mb-2">
                    <input class="form-control form-control-solid-bg form-control-sm batch_number"
                           placeholder="BN" type="text" disabled/>
                </div>
                <div class="col mb-2">
                    <input class="form-control form-control-solid-bg form-control-sm unit_measure"
                           placeholder="UoM" type="text" disabled/>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="text" class="form-control form-control-sm form-control-solid-bg location" readonly
                           placeholder="WH-H-L" name="locations[]"/>
                </div>
                <div class="col-md-2 mb-2 ">
                    <select name="cars_id[]" data-control="select2"
                            class="form-select form-select-solid-bg form-select-sm mb-2 cars" data-placeholder="Car">
                        <option></option>
                        @foreach($cars as $car)
                            <option value="{{ $car->id }}">
                                {{ $car->number}}
                            </option>
                        @endforeach
                    </select>
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
                    <input type="hidden" name="items_id[]" value="{{ $item->id }}"/>
                    <input type="hidden" name="warehouse_item_ids[]" class="warehouse_item_ids"/>
                    <div class="col-3 mb-2 search-wrapper">
                        <select name="products_id[]"
                                data-control="select2"
                                class="form-select form-select-solid-bg form-select-sm mb-2 products"
                                data-placeholder="Product">
                            <option></option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-batch-number ="{{ $product->batch_number }}"
                                        @if($item->WarehouseItem?->product_id == $product->id) selected
                                        data-selected="true" @endif>
                                    @if($product->batch_number)
                                        {{ $product->name .' - '. $product->batch_number}}
                                    @else
                                        {{ $product->name}}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-1 mb-2">
                        <input type="number" min="1" class="form-control form-control-sm quantities"
                               name="quantities[]" step="any"
                               aria-describedby="capacities" placeholder="Qty" value="{{ $item->quantity }}"
                               max="{{ $item->quantity  + $item->WarehouseItem?->remaining_quantity }}"/>
                    </div>
                    <div class="col-1 mb-2">
                        <input type="number" min="1" class="form-control form-control-sm other_quantities"
                               name="other_quantities[]"
                               aria-describedby="capacities" placeholder="Other Qty" value="{{ $item->other_quantity }}"
                               max="{{ $item->other_quantity  + $item->WarehouseItem?->remaining_other_quantity }}"/>
                    </div>
                    <div class="col mb-2">
                        <input class="form-control form-control-solid-bg form-control-sm barcode"
                               placeholder="Barcode" type="text" disabled/>
                    </div>
                    <div class="col mb-2">
                        <input class="form-control form-control-solid-bg form-control-sm batch_number"
                               placeholder="BN" type="text" disabled/>
                    </div>
                    <div class="col mb-2">
                        <input class="form-control form-control-solid-bg form-control-sm unit_measure"
                               placeholder="UoM" type="text" disabled/>
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="text" class="form-control form-control-sm form-control-solid-bg location" readonly
                               placeholder="WH-H-L" name="locations[]"/>
                    </div>
                    <div class="col-md-2 mb-2 ">
                        <select name="cars_id[]" data-control="select2"
                                class="form-select form-select-solid-bg form-select-sm mb-2 cars"
                                data-placeholder="Car">
                            <option></option>
                            @foreach($cars as $car)
                                <option value="{{ $car->id }}" @if($item->outbound_car_id == $car->id) selected @endif>
                                    {{ $car->number}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col mb-2">
                        <button type="button" data-repeater-products-delete
                                class="btn btn-icon btn-light-danger btn-sm">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

