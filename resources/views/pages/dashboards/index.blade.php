<x-default-layout>

    @section('title')
        Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('dashboard') }}
    @endsection

    <div class="row gx-5 gx-xl-10" id="retention_table">
       {!! $view !!}
    </div>

</x-default-layout>
