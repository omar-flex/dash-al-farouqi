<x-default-layout>

    @section('title')
        Guide Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('contract.guides.show', $guide) }}
    @endsection

    <div class="card">
        <div class="card-body py-4">
            @include('pages.apps.contract.guides.tab._information')
        </div>
    </div>

</x-default-layout>
