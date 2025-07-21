<div class="card">
    <div class="card-header">
        <div class="card-title"> Outbounds</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            {{ $dataTable->table() }}
        </div>
    </div>
</div>

@push('scripts')
    {{ $dataTable->scripts() }}
@endpush
