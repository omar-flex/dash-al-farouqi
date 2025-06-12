<div class="row">
    <div class="col-md-4 mb-7"></div>
    <div class="col-md-6 mb-7">
        <label class="required fw-semibold fs-6 mb-2">Cars</label>
        <select class="form-select form-select-solid-bg mb-2" id="cars"
                data-control="select2" data-placeholder="Select an Car">
            <option></option>
            @foreach($outbound->Cars as $cars )
                <option value="{{$cars->id}}">
                    {{$cars->number}}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-12 form-group">
        <a class="btn btn-light-success btn-sm float-end mx-2" id="submit"> Submit </a>
    </div>

</div>

<script>
    $(document).ready(function () {
        $('#cars').select2({
            dropdownParent: $('#modal'),
        });

        $('#submit').click(function () {
            let car_id = $('#cars').val();
            if (car_id) {
                let url = "/operation-management/outbounds/{{$outbound->id}}/output-products/pdf/" + car_id;
                window.open(url, '_blank');
            }
        })
    });
</script>


