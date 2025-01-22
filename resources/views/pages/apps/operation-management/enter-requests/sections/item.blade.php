<form action="{{ route('warehouse-management.locations.update', $location) }}" id="{{$payload->formId}}"
          method="POST"
          enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <div class="row">
            <div class="col-md-4 mb-7">
                <label class="required fw-semibold fs-6 mb-2">Location Name</label>
                <input type="text" name="location_name" class="form-control form-control-solid-bg mb-2"
                       autocomplete="off"
                       placeholder="Location Name"
                       @isset($location) value="{{ $location->name }}" @endisset>
            </div>
            <div class="col-md-4 mb-7">
                <label class="required fw-semibold fs-6 mb-2">Location Code</label>
                <input type="text" name="code" class="form-control form-control-solid-bg mb-2"
                       autocomplete="off"
                       placeholder="Warehouse Code"
                       @isset($location) value="{{ $location->code }}" @endisset>
            </div>
            <div class="col-md-4 mb-7">
                <label class="required fw-semibold fs-6 mb-2">Warehouse</label>
                <select name="warehouse_id" class="form-select form-select-solid mb-2" id="warehouses"
                        data-control="select2" data-placeholder="Select an Warehouse">
                    <option></option>
                    @foreach($payload->warehouses as $warehouse )
                        <option value="{{$warehouse->id}}"
                                @if(isset($location) && $location->warehouse_id == $warehouse->id) selected @endif>
                            {{$warehouse->name .' - ' . $warehouse->code}}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-12 form-group">
            <input type="submit" class="btn btn-light-success btn-sm float-end" value="Submit"
                   id="btn-submit">
        </div>
    </form>

    <script>
        $(document).ready(function () {
            $('#warehouses').select2({
                dropdownParent: $('#modal'),
            });
        });
    </script>
