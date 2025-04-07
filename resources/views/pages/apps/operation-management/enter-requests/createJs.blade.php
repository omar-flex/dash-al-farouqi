<script>
    $(document).ready(function () {
        let clickedButton = null;

        $('input[type="submit"]').click(function () {
            clickedButton = $(this).attr('id');
        });

        $('#enterRequest').submit(function (e) {
            $(".span_error").each(function () {
                $(this).remove()
            });
            e.preventDefault();
            $("#btn-submit,#btn-save").prop("disabled", false)
            var form = $(this);
            let formData = new FormData(this);
            if (clickedButton) {
                formData.append('button_clicked', clickedButton);
            }
            myDropzone.files.forEach(function (file, index) {
                formData.append('files[' + index + ']', file);
            });

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
                        $("#btn-submit,#btn-save").prop("disabled", false)
                        $.each(data.errors, function (index, value) {
                            var error = '<span class="text-danger span_error"> ' + value + '</span>'
                            var parentWithColMd = $('[name="' + index + '"]').closest('[class*="col-md-"]');
                            if (parentWithColMd.length) {
                                parentWithColMd.append(error);
                            } else {
                                $('[name="' + index + '"]').parent().append(error);
                            }
                        });
                        toastr.error('Oops,there were an errors...');
                    } else {
                        toastr.success(data.message);
                        $('#modal').modal('hide');
                        $('#modal-body').empty()
                        window.LaravelDataTables['{{$payload->tableId}}'].ajax.reload();
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    $("#btn-submit,#btn-save").prop("disabled", false)
                    toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                }
            });

        });

        $('#companies,#customers,#countries,#warehouses').select2({
            dropdownParent: $('#modal'),
        });

        $('#countryCheckBox').change(function () {
            let countries = $('#countries')
            if ($(this).is(':checked')) {
                countries.prop("disabled", true)
                countries.val('').trigger('change');
            } else {
                countries.prop("disabled", false)
            }
        });

        @can('add_customers')
        $('#add_customer').on('click', function () {
            $.ajax({
                url: '{{route('customers.create',['enter_request' => true])}}',
                method: 'get',
                success: function (data) {
                    $('#customer-modal-body').html(data);
                    $('#customer-modal-title').text('Add Customer');
                    $('#customer-modal').modal('show');
                    $('#formCustomer').submit(function (e) {
                        e.preventDefault();
                        $(".span_error").each(function () {
                            $(this).remove()
                        });
                        $('#error').empty()
                        $("#btn-submit").prop("disabled", true)
                        var form = $(this);
                        var url = form.attr('action');
                        $.ajax({
                            type: "POST",
                            url: url,
                            data: new FormData(this),
                            dataType: "json",
                            contentType: false,
                            cache: false,
                            processData: false,
                            success: function (data) {
                                if (data.status === 422) {
                                    $("#btn-submit").prop("disabled", false)
                                    $.each(data.errors, function (index, value) {
                                        var error = '<span class="text-danger span_error"> ' + value + '</span>'
                                        if (index.split('.').length > 1) {
                                            $('#error').last().append(error)
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
                                    toastr.success('add Successfully');
                                    $('#customers option').remove();
                                    $('#customers').append("<option> </option>");
                                    $.each(data, function (index, value) {
                                        $('#customers').append("<option value='" + value.id + "'>" + value.name + "</option>");
                                    });
                                    $('#customer-modal').modal('hide');
                                    $('#customer-modal-body').empty()
                                }
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                $("#btn-submit").prop("disabled", false)
                                toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                            }
                        });

                    });
                },
                error: function (xhr) {
                    $("#btn-submit").prop("disabled", false)
                    toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                }
            });
        });
        @endcan

        @can('add_companies')
        $('#add_company').on('click', function () {
            $.ajax({
                url: '{{route('companies.create',['enter_request' => true])}}',
                method: 'get',
                success: function (data) {
                    $('#customer-modal-body').html(data);
                    $('#customer-modal-title').text('Add Clearance Company');
                    $('#customer-modal').modal('show');
                    $('#formCompany').submit(function (e) {
                        e.preventDefault();
                        $(".span_error").each(function () {
                            $(this).remove()
                        });
                        $('#error').empty()
                        $("#btn-submit").prop("disabled", true)
                        var form = $(this);
                        var url = form.attr('action');
                        $.ajax({
                            type: "POST",
                            url: url,
                            data: new FormData(this),
                            dataType: "json",
                            contentType: false,
                            cache: false,
                            processData: false,
                            success: function (data) {
                                if (data.status === 422) {
                                    $("#btn-submit").prop("disabled", false)
                                    $.each(data.errors, function (index, value) {
                                        var error = '<span class="text-danger span_error"> ' + value + '</span>'
                                        if (index.split('.').length > 1) {
                                            $('#error').last().append(error)
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
                                    toastr.success('Stored Successfully');
                                    $('#companies option').remove();
                                    $('#companies').append("<option> </option>");
                                    $.each(data.companies, function (index, value) {
                                        let selected = ''
                                        if (value.id === data.company_id)
                                            selected = 'selected';
                                        $('#companies').append("<option value='" + value.id + "' " + selected + ">" + value.name + "</option>");
                                    });
                                    $('#customer-modal').modal('hide');
                                    $('#customer-modal-body').empty()
                                }
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                $("#btn-submit").prop("disabled", false)
                                toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                            }
                        });

                    });
                },
                error: function (xhr) {
                    $("#btn-submit").prop("disabled", false)
                    toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                }
            });
        });
        @endcan


        let myDropzone = new Dropzone("#dropzone", {
            url: "#",
            acceptedFiles: "application/pdf,image/*",
            autoProcessQueue: false,
            uploadMultiple: true,
            paramName: "images",
            maxFiles: 10,
            maxFilesize: 10,
            addRemoveLinks: true,
        });

        $(document).on('click', '.file_remove_btn', function () {
            var id = $(this).attr('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(function (result) {
                if (result.value) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        url: '/operation-management/enter_requests/files/' + id,
                        method: 'delete',
                        success: function (data) {
                            $('#file_' + id).fadeOut('slow', function () {
                                $('#file_' + id).remove();
                            });
                            toastr.success('Your File has been removed');
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                        }
                    });
                }
            });

        });
    });
</script>
