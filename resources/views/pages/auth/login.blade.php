<x-auth-layout>
    <form class="form w-100" novalidate="novalidate" id="sign_in_form" action="{{ route('login') }}">
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">
                Sign In
            </h1>
        </div>

        <div class="fv-row mb-8">
            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent"/>
        </div>

        <div class="fv-row mb-3">
            <input type="password" placeholder="Password" name="password" autocomplete="off"
                   class="form-control bg-transparent"/>
        </div>


        <div class="d-grid mb-10">
            <button type="submit" id="btn-submit" class="btn btn-primary">
                @include('partials/general/_button-indicator', ['label' => 'Sign In'])
            </button>
        </div>
    </form>


</x-auth-layout>
<script>
    $(document).ready(function () {
        $('#sign_in_form').submit(function (e) {
            $(".span_error").each(function () {
                $(this).remove()
            });
            e.preventDefault();
            let submitButton = $("#btn-submit");
            submitButton.prop("disabled", true)
            submitButton.attr('data-kt-indicator', 'on');
            let form = $(this);
            let url = form.attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: new FormData(this),
                dataType: "json",
                contentType: false,
                cache: false,
                processData: false,
                success: function () {
                    Swal.fire({
                        text: "You have successfully logged in!",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500,
                    });
                    location.href = "{{ route('dashboard') }}";
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $("#btn-submit").prop("disabled", false)
                    submitButton.attr('data-kt-indicator', '');
                    let data = jqXHR.responseJSON
                    $.each(data.errors, function (index, value) {
                        var error = '<span class="text-danger span_error"> ' + value + '</span>'
                        $('[name="' + index + '"]').parent().last().append(error)
                    });
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops,there were an errors...',
                    })
                }
            });

        });
    });

</script>
