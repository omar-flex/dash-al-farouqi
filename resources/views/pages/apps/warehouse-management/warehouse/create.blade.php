@if(isset($warehouse))
    <form action="{{ route('warehouse-management.warehouses.update', $warehouse) }}" id="{{$payload->formId}}"
          method="POST"
          enctype="multipart/form-data">
        @method('PUT')
        @else
            <form action="{{ route('warehouse-management.warehouses.store') }}" method="POST"
                  id="{{$payload->formId}}"
                  enctype="multipart/form-data">
                @endif
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Warehouse Name</label>
                        <input type="text" name="warehouse_name" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Warehouse Name"
                               @isset($warehouse) value="{{ $warehouse->name }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Warehouse Code</label>
                        <input type="text" name="code" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Warehouse Code"
                               @isset($warehouse) value="{{ $warehouse->code }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Status</label>
                        <select name="is_active" class="form-select form-select-solid mb-2" id="statuses"
                                data-control="select2" data-placeholder="Select an Status">
                            <option value="1" @if(isset($warehouse) && $warehouse->is_active) selected @endif> Active
                            </option>
                            <option value="0" @if(isset($warehouse) && !$warehouse->is_active) selected @endif>
                                Inactive
                            </option>
                        </select>
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
