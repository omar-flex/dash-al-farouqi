@if(isset($product))
    <form action="{{ route('products.update', $product) }}" id="{{$payload->formId}}"
          method="POST"
          enctype="multipart/form-data">
        @method('PUT')
        @else
            <form action="{{ route('products.store') }}" method="POST"
                  id="{{$payload->formId}}"
                  enctype="multipart/form-data">
                @endif
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Product name</label>
                        <input type="text" name="product_name" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Product name"
                               @isset($product) value="{{ $product->name }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Barcode</label>
                        <input type="text" name="barcode" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Barcode"
                               @isset($product) value="{{ $product->barcode }}" @endisset>
                    </div>
                    <div class="col-md-2 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Unit Measures</label>
                        <select name="unit_measure_id" class="form-select form-select-solid mb-2" id="unit_measures"
                                data-control="select2" data-placeholder="Select an Unit Measures">
                            <option></option>
                            @foreach($payload->unit_measures as $unit_measure )
                                <option value="{{$unit_measure->id}}"
                                        @if(isset($product) && $product->unit_measure_id == $unit_measure->id) selected @endif>
                                    {{$unit_measure->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea name="description" class="form-control form-control-solid-bg mb-2"
                                  autocomplete="off" placeholder="Description"> @isset($product){{ $product->description }}@endisset</textarea>

                    </div>

                </div>

                <div class="col-md-12 form-group">
                    <input type="submit" class="btn btn-light-success btn-sm float-end" value="Submit"
                           id="btn-submit">
                </div>

            </form>
    </form>

    <script>
        $(document).ready(function () {
            $('#unit_measures').select2({
                dropdownParent: $('#modal'),
            });
        });
    </script>
