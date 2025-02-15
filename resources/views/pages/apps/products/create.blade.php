@if(isset($customer))
    <form action="{{ route('products.update', $customer) }}" id="{{$payload->formId}}"
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
                    <div class="col-md-12 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Product name</label>
                        <input type="text" name="product_name" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Product name"
                               @isset($customer) value="{{ $customer->name }}" @endisset>
                    </div>
                    <div class="col-md-12 mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea name="description" class="form-control form-control-solid-bg mb-2"
                                  autocomplete="off"
                                  placeholder="Description"> @isset($customer){{ $customer->description }}@endisset</textarea>

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
            $('#statuses').select2({
                dropdownParent: $('#modal'),
            });
        });
    </script>
