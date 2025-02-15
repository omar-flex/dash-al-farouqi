@php
    $is_cars = $enterRequest->cars()->count() > 0;
    $lines = [];
@endphp
<style>
    #suggestions {
        position: sticky;
        top: calc(100% + 5px);
        z-index: 1000;
        width: 100%;
    }
</style>
<div class="card card-flush mb-6 mb-xl-9">
    <form action="{{ route('operation-management.enter_requests.products.store',$enterRequest->id) }}" method="POST"
          id="formProducts"
          enctype="multipart/form-data">
        @csrf
        <div class="card-header mt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1"> Product item with Locations</h2>
            </div>
            <div class="card-toolbar">
                <input type="submit" class="btn btn-light-success btn-sm float-end mx-2" value="save"
                       id="btn-submit">
            </div>
        </div>
        <div class="card-body p-9 pt-4">
            <div class="row px-3">
                <div class="col-2 mb-3">
                    <label class="fw-semibold fs-6 mb-3 required" title="Product"> Product </label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-6 mb-2" title="Lot number"> Batch Number </label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-6 mb-2 required" title="Unit of Measure"> UoM </label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-6 mb-2 required" title="Quantity">Quantity</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-6 mb-2 required" title="Location">WH-H-L</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-6 mb-2" title="Level">Level</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-6 mb-2" title="Pallet">Pallet</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-6 mb-2" title="Remove">Remove</label>
                </div>

            </div>
            <div data-repeater-products-list>
                @if(count($lines) == 0)
                    <div class="row px-3" data-repeater-products-item>
                        <div class="col-2 mb-2 search-wrapper">
                            <input type="text" name="products[]"
                                   class="form-control form-control-solid-bg form-control-sm searchInput"
                                   autocomplete="off" placeholder="search...">
                            <div class="suggestions list-group"></div>
                        </div>
                        <div class="col mb-2">
                            <input class="form-control form-control-solid-bg form-control-sm codes"
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
                            <input type="number" min="1" class="form-control form-control-sm"
                                   name="quantities[]"
                                   aria-describedby="capacities" placeholder="Qty"/>
                        </div>
                        <div class="col mb-2">
                            <select name="locations[]"
                                    class="form-select form-select-solid-bg form-select-sm mb-2 locations"
                                    data-control="select2"
                                    data-placeholder="WH-H-L">
                                <option></option>
                                @foreach($payload->locations as $location)
                                    <option
                                        value="{{ Illuminate\Support\Arr::first($location) }}"> {{ Illuminate\Support\Arr::last($location)}} </option>
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
                    {{--    @foreach($lines as $line)
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
                                                    @if($line->category_id == $category->id ) selected @endif >
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
                        @endforeach--}}
                @endif
            </div>
            <div class="form-group mt-3 text-end px-3">
                <button type="button" data-repeater-products-create class="btn btn-sm btn-light-primary">
                    <i class="ki-duotone ki-plus fs-2"></i>
                    Add Line
                </button>
            </div>

        </div>
    </form>
</div>

@push('scripts')
    <script>
        function initSelect2() {
            $('.unit_measures,.categories,.locations').select2();
        }

        function checkVariantDetectability() {
            if ($('[data-repeater-products-item]').length == 1) {
                $('[data-repeater-products-delete]').prop('disabled', true).addClass('disabled');
            } else {
                $('[data-repeater-products-delete]').prop('disabled', false).removeClass('disabled');
            }
        }

        $(document).ready(function () {
            $(document).on('input', '.searchInput', function (e) {
                let query = $(this).val().trim();
                let $wrapper = $(this).closest('.search-wrapper');
                let $suggestions = $wrapper.find('.suggestions');

                if (query.length < 2) {
                    $suggestions.empty();
                    return;
                }

                $.ajax({
                    url: '/products-search',
                    method: 'GET',
                    data: {q: query},
                    dataType: 'json',
                    success: function (data) {
                        $suggestions.empty();
                        if (data.length > 0) {
                            $.each(data, function (index, item) {
                                $suggestions.append(
                                    `<a href="#" class="list-group-item list-group-item-action suggestion-item" data-id="${item.id}">${item.name}</a>`
                                );
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(error);
                    }
                });
            });

            $(document).on('keydown', '.searchInput', function (e) {
                let $wrapper = $(this).closest('.search-wrapper');
                let $suggestions = $wrapper.find('.suggestions');
                let $items = $suggestions.find('.suggestion-item');

                if ($items.length === 0) return;

                let currentIndex = $items.index($suggestions.find('.active'));

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentIndex++;
                    if (currentIndex >= $items.length) currentIndex = 0;
                    $items.removeClass('active');
                    $items.eq(currentIndex).addClass('active');
                }
                else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentIndex--;
                    if (currentIndex < 0) currentIndex = $items.length - 1;
                    $items.removeClass('active');
                    $items.eq(currentIndex).addClass('active');
                }
                else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentIndex >= 0) {
                        let text = $items.eq(currentIndex).text();
                        $(this).val(text);
                        $suggestions.empty();
                    }
                }
            });

            $(document).on('click', '.suggestion-item', function (e) {
                e.preventDefault();
                let text = $(this).text();
                let $wrapper = $(this).closest('.search-wrapper');
                $wrapper.find('.searchInput').val(text);
                $wrapper.find('.suggestions').empty();
            });


            $("[data-repeater-products-create]").click(function () {
                let repeaterList = $("[data-repeater-products-list]");
                let newItem = repeaterList.find("[data-repeater-products-item]:first").clone();

                newItem.find("input").val("");
                newItem.find("select").prop("selectedIndex", 0);
                newItem.find('.select2-container').remove();
                newItem.find('.suggestions').empty();
                newItem.find('.span_error').each(function () {
                    $(this).remove()
                });
                repeaterList.append(newItem);
                initSelect2();
                checkVariantDetectability();
            });
            $(document).on("click", "[data-repeater-products-delete]", function () {
                if ($('[data-repeater-products-item]').length > 1) {
                    $(this).closest("[data-repeater-products-item]").remove();
                }
                checkVariantDetectability();
            });
            initSelect2()

            $('#formProducts').submit(function (e) {
                e.preventDefault();
                $(".span_error").each(function () {
                    $(this).remove()
                });
                $("#btn-submit").prop("disabled", false)
                var form = $(this);
                let formData = new FormData(this);
                var url = form.attr('action');
                $.ajax({
                    type: "POST",
                    url: url,
                    data: formData,
                    dataType: "json",
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function (data) {
                        if (data.status === 422) {
                            $("#btn-submit").prop("disabled", false)
                            $.each(data.errors, function (index, value) {
                                var error = '<span class="text-danger span_error"> ' + value + '</span>'
                                let repeaterList = $("[data-repeater-products-list]");
                                if (index.split('.').length > 1) {
                                    const parts = index.split('.');
                                    let line = parts[1];
                                    let name = parts[0] + '[]';
                                    repeaterList.children().eq(line).find('[name="' + name + '"]').parent().last().append(error)
                                } else {
                                    let input = $('[name="' + index + '"]').parent().last()
                                    if (input.length > 0) {
                                        input.append(error)
                                    } else {
                                        $('#error').append(error)
                                    }
                                }
                            });
                            toastr.error('Oops,there were an errors...');
                        } else {
                            toastr.success(data.message);
                            location.reload(true);
                            //$('#div_cars').empty().append(data.html)
                            $('#btn-submit').addClass('d-none')
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        $("#btn-submit").prop("disabled", false)
                        toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                    }
                });

            });
        });
    </script>
@endpush
