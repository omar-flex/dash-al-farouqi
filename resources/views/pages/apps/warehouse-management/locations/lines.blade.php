<form action="{{ route('warehouse-management.line-locations-update',$location_id) }}" method="POST"
      id="{{$payload->formId}}"
      enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-md-12 mb-2">
            <div class="card mb-5 mb-xl-8">
                <div class="card-body py-5 px-2">

                    <label class="fw-semibold fs-6 mx-2"><span class="text-primary">{{$location->warehouse->name}}</span>
                        ( <strong>{{$location->warehouse->code}}</strong> )
                        - <span class="text-primary">{{$location->name}}</span> ( <strong>{{$location->code}} </strong> )
                    </label>

                    <hr class="text-gray-200">
                    <div class="row px-3">
                        <div class="col-md-3 mb-3">
                            <label class="required fw-semibold fs-6 mb-2">Name Line</label>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="fw-semibold fs-6 mb-2 required">Code</label>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold fs-6 mb-2 required">Storage Category</label>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold fs-6 mb-2 required">Capacity </label>
                        </div>
                    </div>
                    <div data-repeater-list>
                        @if(count($lines) == 0)
                            <div class="row px-3" data-repeater-item>
                                <div class="col-md-3 mb-2">
                                    <input class="form-control form-control-solid-bg form-control-sm name_lines"
                                           placeholder="Name Line" type="text" name="name_lines[]"/>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <input class="form-control form-control-solid-bg form-control-sm codes"
                                           placeholder="Code" type="text" name="codes[]"/>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <select name="categories[]"
                                            class="form-select form-select-solid-bg form-select-sm mb-2 categories"
                                            data-control="select2"
                                            data-placeholder="Select an Storage Category">
                                        <option></option>
                                        @foreach($payload->categories as $category)
                                            <option value="{{ $category->id }}">
                                                {{ $category->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="input-group input-group-sm mb-5">
                                        <input type="number" step=any min="1" class="form-control" name="capacities[]"
                                               aria-describedby="capacities" placeholder="Capacity"/>
                                        <span class="input-group-text" id="capacities">cpm</span>
                                    </div>
                                </div>
                                <div class="col-md-1 mb-2">
                                    <button type="button" data-repeater-delete=""
                                            class="btn btn-icon btn-light-danger btn-sm">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            @foreach($lines as $line)
                                <div class="row px-3" data-repeater-item>
                                    <input type="hidden" name="lines_id[]" value="{{$line->id}}"/>
                                    <div class="col-md-3 mb-2">
                                        <input class="form-control form-control-solid-bg form-control-sm name_lines"
                                               placeholder="Name Line" type="text" name="name_lines[]"
                                               value="{{$line->name}}"/>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <input class="form-control form-control-solid-bg form-control-sm codes"
                                               placeholder="Code" type="text" name="codes[]" value="{{$line->code}}"/>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <select name="categories[]"
                                                class="form-select form-select-solid-bg form-select-sm mb-2 categories"
                                                data-control="select2"
                                                data-placeholder="Select an Storage Category">
                                            <option></option>
                                            @foreach($payload->categories as $category)
                                                <option value="{{ $category->id }}"
                                                        @if($line->category_id && $category->id ) selected @endif >
                                                    {{ $category->name}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="input-group input-group-sm mb-5">
                                            <input type="number" step=any min="1" class="form-control"
                                                   name="capacities[]"
                                                   aria-describedby="capacities" placeholder="Capacity"
                                                   value="{{$line->capacity}}"/>
                                            <span class="input-group-text" id="capacities">cpm</span>
                                        </div>
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <button type="button" data-repeater-delete=""
                                                class="btn btn-icon btn-light-danger btn-sm">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                    </div>
                    <div class="form-group mt-3 text-end px-3">
                        <button type="button" data-repeater-create class="btn btn-sm btn-light-primary">
                            <i class="ki-duotone ki-plus fs-2"></i>
                            Add Line
                        </button>
                    </div>
                </div>
                <div class="card-footer border-0">
                    <div id="error"></div>
                </div>
            </div>
        </div>

        <div class="col-md-12 form-group">
            <input type="submit" class="btn btn-light-success btn-sm float-end" value="Submit" id="btn-submit">
        </div>
    </div>

</form>

<script>
    function initSelect2() {
        $('.categories').select2({
            dropdownParent: $('#modal-body')
        });
    }

    function checkVariantDetectability() {
        if ($('[data-repeater-item]').length <= 1) {
            $('[data-repeater-delete]').prop('disabled', true).addClass('disabled');
        } else {
            $('[data-repeater-delete]').prop('disabled', false).removeClass('disabled');
        }
    }

    $(document).ready(function () {
        $("[data-repeater-create]").click(function () {
            let repeaterList = $("[data-repeater-list]");
            var newItem = repeaterList.find("[data-repeater-item]:first").clone();
            newItem.find("input").val("");
            newItem.find("select").prop("selectedIndex", 0);
            newItem.find('.select2-container').remove();
            newItem.find('.span_error').each(function () {
                $(this).remove()
            });
            repeaterList.append(newItem);
            initSelect2();
            checkVariantDetectability();
        });
        $(document).on("click", "[data-repeater-delete]", function () {
            if ($('[data-repeater-item]').length > 1) {
                $(this).closest("[data-repeater-item]").remove();
            }
            checkVariantDetectability();
        });
        initSelect2()
    });
</script>
