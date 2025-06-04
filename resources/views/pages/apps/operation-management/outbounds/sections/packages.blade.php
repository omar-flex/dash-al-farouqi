<style>
    .span_error {
        font-size: 10px;
    }
</style>
@php
    if(!auth()->user()->hasRole('administrator') && ($outbound->status_id == \App\Models\OutboundStatus::VALIDATION || $outbound->status_id == \App\Models\OutboundStatus::AUTHORIZATION))
        $disabled  = 'disabled';
    else
        $disabled  = null;
if($outbound->status_id == \App\Models\OutboundStatus::APPROVED)
 $disabled  = 'disabled';
@endphp

<div class="card card-flush mb-6 mb-xl-9" id="product_items">
    <form action="{{ route('operation-management.outbounds.products.store',$outbound->id) }}" method="POST"
          id="formProducts"
          enctype="multipart/form-data">
        @csrf
        <div class="card-header mt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1"> Product item with Locations</h2>
            </div>
            <div class="card-toolbar">
                @if(!$disabled)
                    <input type="submit" class="btn btn-light-success btn-sm float-end mx-2" value="save"
                           id="btn-submit">
                    <input type="submit" class="btn btn-light-warning btn-sm float-end" value="Save as Draft"
                           id="btn-draft">
                @endif
            </div>
        </div>
        <div class="card-body p-9 pt-4">
            <div class="row px-3">
                <div class="col-3 mb-3">
                    <label class="fw-semibold fs-7 mb-3  @if(!$disabled) required @endif" title="Product">
                        Product </label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-7 mb-2  @if(!$disabled) required @endif"
                           title="Quantity">Quantity</label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-7 mb-2"
                           title="Quantity">Other Qty</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Lot number"> Barcode </label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Batch Number"> BN </label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Unit Measures"> UoM</label>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="fw-semibold fs-7 mb-2 " title="Location">Location</label>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="fw-semibold fs-7 mb-2 " title="Car">Car</label>
                </div>
                @if(!$disabled)
                    <div class="col mb-3">
                        <label class="fw-semibold fs-7 mb-2" title="Remove">Remove</label>
                    </div>
                @endif
            </div>
            @include('pages.apps.operation-management.outbounds.sections.products_items')
            @if(!$disabled)
                <div class="form-group mt-3 text-end px-3">
                    <button type="button" data-repeater-products-create class="btn btn-sm btn-light-primary">
                        <i class="ki-duotone ki-plus fs-2"></i>
                        Add Line
                    </button>
                </div>
            @endif
        </div>
    </form>
</div>
@push('scripts')
    <script>
        function initSelect2() {
            $('.products,.cars').select2();
        }

        function getProductsInfo(product, is_edit = true) {
            let product_id = product.val();
            let parent = product.parent().parent()
            $.ajax({
                url: '/operation-management/outbounds/{{$outbound->id}}/products/' + product_id,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    parent.find('.warehouse_item_ids').val(data.id).attr('title', data.id)
                    parent.find('.barcode').val(data.product.barcode).attr('title', data.product.barcode)
                    parent.find('.unit_measure').val(data.product.unit_measure.name).attr('title', data.product.unit_measure.name)
                    parent.find('.batch_number').val(data.batch_number).attr('title', data.batch_number)
                    if (is_edit) {
                        parent.find('.quantities').attr('max', data.quantity).val(data.quantity).attr('title', data.quantity)
                        parent.find('.other_quantities').attr('max', data.other_quantity ).val(data.other_quantity ).attr('title', data.other_quantity )
                    }
                    parent.find('.location').val(data.location).attr('title', data.location)
                },
                error: function (xhr, status, error) {
                    toastr.error(xhr.status + ' : ' + error);
                }
            });
        }

        function sumQuantities() {
            let sum = 0;
            document.querySelectorAll('input[name="quantities[]"]').forEach(input => {
                sum += parseFloat(input.value) || 0;
            });
            return sum;
        }

        function checkVariantDetectability() {
            if ($('[data-repeater-products-item]').length == 1) {
                $('[data-repeater-products-delete]').prop('disabled', true).addClass('disabled');
            } else {
                $('[data-repeater-products-delete]').prop('disabled', false).removeClass('disabled');
            }
        }

        $(document).ready(function () {

            @isset($warehouseItems)
            $('#product_items .products').each(function () {
                const selectedOption = $(this).find('option:selected[data-selected="true"]');
                if (selectedOption.length > 0) {
                    getProductsInfo($(this), false);
                }
            });
            @endisset

            $(document).on('change', '.products', function (e) {
                e.preventDefault();
                getProductsInfo($(this), true)
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
            let clickedButton = null;

            $('input[type="submit"]').click(function () {
                clickedButton = $(this).attr('id');
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
                $("#btn-submit,#btn-draft").prop("disabled", true)
                if (clickedButton === 'btn-submit') {
                    Swal.fire({
                        title: 'Are you sure?',
                        html: "<span> You won't be able to <span class='text-danger'> Manifest Validation! </span> </span>",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, change it!'
                    }).then(function (result) {
                        if (result.value) {
                            {{-- if (sumQuantities() !== {{$outbound->quantity_packages}}){
                                toastr.error('Quantity Product (' + sumQuantities() + ') Must equal packages Count ({{$outbound->quantity_packages}})')
                                $("#btn-submit,#btn-draft").prop("disabled", false)
                                return false;
                            } --}}
                            let form = $("#formProducts");
                            let formData = new FormData(form[0]);
                            if (clickedButton) {
                                formData.append('button_clicked', clickedButton);
                            }
                            let url = form.attr('action');
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
                                        $("#btn-submit,#btn-draft").prop("disabled", false)
                                        $.each(data.errors, function (index, value) {
                                            let error = '<span class="text-danger span_error"> ' + value + '</span>'
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
                                        //$('#product_items').empty().append(data.html)
                                        toastr.success(data.message);
                                        location.reload(true);

                                    }
                                },
                                error: function (xhr, ajaxOptions, thrownError) {
                                    $("#btn-submit,#btn-draft").prop("disabled", false)
                                    toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                                }
                            });
                        } else {
                            $("#btn-submit,#btn-draft").prop("disabled", false)
                        }
                    });
                } else {
                    let form = $(this);
                    let formData = new FormData(this);
                    if (clickedButton) {
                        formData.append('button_clicked', clickedButton);
                    }
                    let url = form.attr('action');

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
                                $("#btn-submit,#btn-draft").prop("disabled", false)
                                $.each(data.errors, function (index, value) {
                                    let error = '<span class="text-danger span_error"> ' + value + '</span>'
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
                                //$('#product_items').empty().append(data.html)
                                toastr.success(data.message);
                                location.reload(true);

                            }
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            $("#btn-submit,#btn-draft").prop("disabled", false)
                            toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                        }
                    });
                }


            });
        });
    </script>
@endpush
