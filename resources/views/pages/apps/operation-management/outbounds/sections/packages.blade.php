<style>
    .product-validation-error {
        font-size: 10px;
    }
</style>
@php
    $canEditProducts = auth()->user()->can('edit_outbounds')
        && in_array((int) $outbound->status_id, [
            \App\Models\OutboundStatus::WH_RELEASE_PRODUCT,
            \App\Models\OutboundStatus::NEED_REVISION,
        ], true);
    $disabled = !$canEditProducts;
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
                           id="products-submit" data-product-action="submit">
                    <input type="submit" class="btn btn-light-warning btn-sm float-end" value="Save as Draft"
                           id="products-draft" data-product-action="draft">
                @endif
            </div>
        </div>
        <div class="card-body p-9 pt-4">
            <div id="product-errors" class="alert alert-danger d-none" role="alert" aria-live="polite"></div>
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
        $(function () {
        const outboundPackageQuantity = Number(@json((float) $outbound->quantity_packages));
        let productFormAction = 'draft';

        function productRows() {
            return $('#formProducts').find('[data-repeater-products-list] > [data-repeater-products-item]');
        }

        function reindexProductRows() {
            productRows().each(function (index) {
                $(this).find('[data-field]').each(function () {
                    $(this).attr('name', `items[${index}][${$(this).data('field')}]`);
                });
            });
        }

        function initProductSelects(scope = document) {
            $(scope).find('.warehouse-items,.cars').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({width: '100%'});
                }
            });
        }

        function setWarehouseItemDetails(select) {
            const row = select.closest('[data-repeater-products-item]');
            const option = select.find('option:selected');
            let available = Number(option.attr('data-available') || 0);
            let otherAvailable = Number(option.attr('data-other-available') || 0);

            row.find('.barcode').val(option.attr('data-barcode') || '');
            row.find('.batch_number').val(option.attr('data-batch-number') || '');
            row.find('.unit_measure').val(option.attr('data-unit-measure') || '');
            row.find('.location').val(option.attr('data-location') || '');
            row.find('.quantities').attr('max', available > 0 ? available : 0);
            row.find('.other_quantities').attr('max', otherAvailable > 0 ? otherAvailable : 0);
        }

        function sumQuantities() {
            let sum = 0;
            productRows().find('[data-field="quantity"]').each(function () {
                sum += Number($(this).val()) || 0;
            });
            return Number(sum.toFixed(3));
        }

        function checkVariantDetectability() {
            const onlyOneRow = productRows().length === 1;
            $('#formProducts').find('[data-repeater-products-delete]')
                .prop('disabled', onlyOneRow)
                .toggleClass('disabled', onlyOneRow);
        }

        function clearValidationErrors() {
            const form = $('#formProducts');
            form.find('.product-validation-error').remove();
            form.find('#product-errors').empty().addClass('d-none');
        }

        function showValidationErrors(errors) {
            clearValidationErrors();
            $.each(errors || {}, function (key, messages) {
                const parts = key.split('.');
                const message = Array.isArray(messages) ? messages[0] : messages;
                let target = $();

                if (parts[0] === 'items' && parts.length >= 3) {
                    target = productRows().eq(Number(parts[1])).find(`[data-field="${parts[2]}"]`).parent().last();
                }

                if (target.length) {
                    $('<span>', {
                        class: 'text-danger product-validation-error',
                        text: String(message),
                    }).appendTo(target);
                } else {
                    const summary = $('#formProducts').find('#product-errors').removeClass('d-none');
                    $('<div>', {
                        class: 'product-validation-error',
                        text: String(message),
                    }).appendTo(summary);
                }
            });
            toastr.error('Please correct the highlighted inventory lines.');
        }

        function setProductButtonsDisabled(disabled) {
            $('#formProducts').find('[data-product-action]').prop('disabled', disabled);
        }

        function submitProductForm(action) {
            const form = $('#formProducts');
            reindexProductRows();
            const formData = new FormData(form[0]);
            formData.set('action', action);

            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: formData,
                dataType: 'json',
                contentType: false,
                cache: false,
                processData: false,
                success: function (data) {
                    if (data.status === 422) {
                        setProductButtonsDisabled(false);
                        showValidationErrors(data.errors);
                        return;
                    }

                    toastr.success(data.message);
                    location.reload(true);
                },
                error: function (xhr) {
                    setProductButtonsDisabled(false);
                    if (xhr.status === 422) {
                        showValidationErrors(xhr.responseJSON?.errors);
                        return;
                    }
                    toastr.error(xhr.responseJSON?.message || xhr.responseJSON?.exception || `${xhr.status}: Request failed`);
                }
            });
        }

            reindexProductRows();
            initProductSelects();
            checkVariantDetectability();

            $('#formProducts').on('change', '.warehouse-items', function () {
                setWarehouseItemDetails($(this));
            });

            $('#formProducts').on('click', '[data-repeater-products-create]', function () {
                const repeaterList = $('#formProducts').find('[data-repeater-products-list]');
                const newItem = productRows().first().clone(false, false);

                newItem.find('.select2-container').remove();
                newItem.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id aria-hidden tabindex').val('');
                newItem.find('option').removeAttr('data-select2-id');
                newItem.find('input').val('');
                newItem.find('[data-field="id"]').val('');
                newItem.attr('data-original-warehouse-item-id', '');
                newItem.attr('data-original-quantity', '0');
                newItem.attr('data-original-other-quantity', '0');
                newItem.find('.product-validation-error').remove();
                repeaterList.append(newItem);

                reindexProductRows();
                initProductSelects(newItem);
                checkVariantDetectability();
            });

            $('#formProducts').on('click', '[data-repeater-products-delete]', function () {
                if (productRows().length > 1) {
                    $(this).closest('[data-repeater-products-item]').remove();
                    reindexProductRows();
                }
                checkVariantDetectability();
            });

            $('#formProducts').on('click', '[data-product-action]', function () {
                productFormAction = $(this).data('product-action');
            });

            $('#formProducts').on('submit', function (event) {
                event.preventDefault();
                const submitterAction = $(event.originalEvent?.submitter).data('product-action');
                const action = submitterAction || productFormAction;
                clearValidationErrors();
                setProductButtonsDisabled(true);

                if (action !== 'submit') {
                    submitProductForm(action);
                    return;
                }

                const productQuantity = sumQuantities();
                if (Math.abs(productQuantity - outboundPackageQuantity) > 0.0005) {
                    showValidationErrors({
                        items: [`Product quantity (${productQuantity}) must equal package count (${outboundPackageQuantity}).`],
                    });
                    setProductButtonsDisabled(false);
                    return;
                }

                Swal.fire({
                    title: 'Submit inventory release?',
                    text: 'The inventory lines will be sent to manifest validation.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, submit'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        submitProductForm(action);
                    } else {
                        setProductButtonsDisabled(false);
                    }
                });
            });
        });
    </script>
@endpush
