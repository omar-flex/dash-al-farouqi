<style>
    #suggestions {
        position: sticky;
        top: calc(100% + 5px);
        z-index: 1000;
        width: 100%;
    }

    .validation-form-error {
        font-size: 10px;
    }
</style>
@php
    $canEditValidation = auth()->user()->can('edit_outbounds')
        && in_array((int) $outbound->status_id, [
            \App\Models\OutboundStatus::VALIDATION,
            \App\Models\OutboundStatus::NEED_REVISION,
        ], true);
    $disabled = !$canEditValidation;
@endphp
<div class="card card-flush mb-6 mb-xl-9">
    <form action="{{ route('operation-management.outbounds.validations.store',$outbound->id) }}" method="POST"
          id="formValidations"
          enctype="multipart/form-data">
        @csrf
        <div class="card-header mt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1"> Validation Product Item</h2>
            </div>
            <div class="card-toolbar">
                @if(!$disabled)
                    <input type="submit" class="btn btn-light-success btn-sm float-end mx-2" value="save"
                           id="validations-submit" data-validation-action="btn-submit">
                    <input type="submit" class="btn btn-light-warning btn-sm float-end" value="Save as Draft"
                           id="validations-draft" data-validation-action="btn-draft">
                @endif
            </div>
        </div>
        <div class="card-body p-9 pt-4">
            <div id="validation-errors" class="alert alert-danger d-none" role="alert" aria-live="polite"></div>
            <div class="row px-3">
                <div class="col-2 mb-3">
                    <label class="fw-semibold fs-7 mb-3" title="Product"> Product </label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Unit of Measure"> UoM </label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Quantity">Quantity</label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Quantity">Other Qty</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2  @if(!$disabled) required @endif" title="Customs Tariff Code">Customs
                        Tariff
                        Code</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2 @if(!$disabled) required @endif" title="Customs Value">Customs
                        Value</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2 @if(!$disabled) required @endif" title="Gross Weight">Gross
                        Weight</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2 @if(!$disabled) required @endif" title="Net Weight">Net
                        Weight</label>
                </div>
            </div>
            @include('pages.apps.operation-management.outbounds.sections.validation_items')
        </div>
    </form>
</div>
@push('scripts')
    <script>
        $(function () {
        const expectedValidationCustomValue = Number(@json((float) $outbound->total_cost));
        const expectedValidationGrossWeight = Number(@json((float) $outbound->gross_weight));
        const expectedValidationNetWeight = Number(@json((float) $outbound->net_weight));

        function sumCustomValue() {
            let sum = 0;
            document.querySelectorAll('#formValidations input[name="custom_values[]"]').forEach(input => {
                sum += parseFloat(input.value) || 0;
            });
            return parseFloat(sum.toFixed(3));
        }
        function sumGrossWeight() {
            let sum = 0;
            document.querySelectorAll('#formValidations input[name="gross_weights[]"]').forEach(input => {
                sum += parseFloat(input.value) || 0;
            });
            return parseFloat(sum.toFixed(3));
        }
        function sumNetWeight() {
            let sum = 0;
            document.querySelectorAll('#formValidations input[name="net_weights[]"]').forEach(input => {
                sum += parseFloat(input.value) || 0;
            });
            return parseFloat(sum.toFixed(3));
        }

        function setValidationButtonsDisabled(disabled) {
            $('#formValidations').find('[data-validation-action]').prop('disabled', disabled);
        }

        function clearValidationFormErrors() {
            const form = $('#formValidations');
            form.find('.validation-form-error').remove();
            form.find('#validation-errors').empty().addClass('d-none');
        }

        function showValidationFormErrors(errors) {
            const form = $('#formValidations');
            clearValidationFormErrors();

            $.each(errors || {}, function (key, messages) {
                const parts = key.split('.');
                const message = Array.isArray(messages) ? messages[0] : messages;
                let input = form.find(`[name="${key}"]`).parent().last();

                if (parts.length > 1) {
                    input = form.find('[data-repeater-validations-list]')
                        .children()
                        .eq(Number(parts[1]))
                        .find(`[name="${parts[0]}[]"]`)
                        .parent()
                        .last();
                }

                if (input.length) {
                    $('<span>', {
                        class: 'text-danger validation-form-error',
                        text: String(message),
                    }).appendTo(input);
                } else {
                    const summary = form.find('#validation-errors').removeClass('d-none');
                    $('<div>', {
                        class: 'validation-form-error',
                        text: String(message),
                    }).appendTo(summary);
                }
            });

            toastr.error('Please correct the highlighted validation lines.');
        }

            let clickedButton = null;

            $('#formValidations').on('click', '[data-validation-action]', function () {
                clickedButton = $(this).data('validation-action');
            });

            $('#formValidations').submit(function (e) {
                e.preventDefault();
                clickedButton = $(e.originalEvent?.submitter).data('validation-action') || clickedButton || 'btn-draft';
                clearValidationFormErrors();
                setValidationButtonsDisabled(true);
                if (clickedButton === 'btn-submit') {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'The validation lines will be sent to manifest authorization.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, change it!'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            if (Math.abs(sumCustomValue() - expectedValidationCustomValue) > 0.0005) {
                                showValidationFormErrors({
                                    custom_values: [`Sum Customs Value (${sumCustomValue()}) must equal ${expectedValidationCustomValue}.`],
                                });
                                setValidationButtonsDisabled(false);
                                return false;
                            }
                            if (Math.abs(sumGrossWeight() - expectedValidationGrossWeight) > 0.0005) {
                                showValidationFormErrors({
                                    gross_weights: [`Sum Gross Weight (${sumGrossWeight()}) must equal ${expectedValidationGrossWeight}.`],
                                });
                                setValidationButtonsDisabled(false);
                                return false;
                            }
                            if (Math.abs(sumNetWeight() - expectedValidationNetWeight) > 0.0005) {
                                showValidationFormErrors({
                                    net_weights: [`Sum Net Weight (${sumNetWeight()}) must equal ${expectedValidationNetWeight}.`],
                                });
                                setValidationButtonsDisabled(false);
                                return false;
                            }
                            let form = $("#formValidations");
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
                                        setValidationButtonsDisabled(false);
                                        showValidationFormErrors(data.errors);
                                    } else {
                                        toastr.success(data.message);
                                        location.reload(true);

                                    }
                                },
                                error: function (xhr) {
                                    setValidationButtonsDisabled(false);
                                    if (xhr.status === 422) {
                                        showValidationFormErrors(xhr.responseJSON?.errors);
                                        return;
                                    }
                                    toastr.error(xhr.responseJSON?.message || xhr.responseJSON?.exception || `${xhr.status}: Request failed`);
                                }
                            });
                        } else {
                            setValidationButtonsDisabled(false);
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
                                setValidationButtonsDisabled(false);
                                showValidationFormErrors(data.errors);
                            } else {
                                toastr.success(data.message);
                                location.reload(true);

                            }
                        },
                        error: function (xhr) {
                            setValidationButtonsDisabled(false);
                            if (xhr.status === 422) {
                                showValidationFormErrors(xhr.responseJSON?.errors);
                                return;
                            }
                            toastr.error(xhr.responseJSON?.message || xhr.responseJSON?.exception || `${xhr.status}: Request failed`);
                        }
                    });
                }


            });
        });
    </script>
@endpush
