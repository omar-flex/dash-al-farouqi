<x-default-layout>

    @section('title')
        Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('dashboard') }}
    @endsection

  {{--      <style>
            .card-3d {
                transform-style: preserve-3d;
                transition: transform 0.5s ease, box-shadow 0.3s ease;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                background: linear-gradient(145deg, #ffffff, #f0f0f0);
                border: none;
            }

            .card-3d:hover {
                transform: scale(1.02) rotateX(4deg) rotateY(4deg);
                box-shadow: 0 25px 40px rgba(0, 0, 0, 0.2);
            }

            .card-header.bg-primary {
                background: linear-gradient(135deg, #2b6cb0, #3182ce);
                border-radius: 16px 16px 0 0;
            }
        </style>
    @if(auth()->user()->hasRole('administrator'))
        <div class="app-container  container-xxl ">
            <div class="row gx-5 gx-xl-10 mb-xl-10">
                <h2 class="mb-4">3D Storage Report</h2>

                @foreach($warehouses as $warehouse)
                    <div class="card-3d mb-4">
                        <div class="card-header bg-primary text-white">
                            🏢 Warehouse: {{ $warehouse->name }}
                        </div>
                        <div class="card-body">
                            @foreach($warehouse->locations as $location)
                                <div class="mb-3 border p-3 rounded">
                                    <h5 class="text-secondary">📍 Location: {{ $location->name }} (Code: {{ $location->code }})</h5>
                                    <div class="row">
                                        @forelse($location->lines as $line)
                                            <div class="col-md-4">
                                                <div class="card-3d mb-3 p-3">
                                                    <h6 class="card-title">{{ $line->name_line }} ({{ $line->code }})</h6>
                                                    <p class="mb-1">🧊 Storage Type: <strong>{{ ucfirst($line->storage_category) }}</strong></p>

                                                    @php
                                                        $stock = $line->items_sum_quantity ?? 0;
                                                        $remaining = $line->capacity - $stock;
                                                        $percent = $line->capacity > 0 ? round(($stock / $line->capacity) * 100, 1) : 0;
                                                    @endphp

                                                    <p class="mb-1">📦 Capacity: <strong>{{ $line->capacity }} CPM</strong></p>
                                                    <p class="mb-1">✅ Current Stock: <strong>{{ $stock }} CPM</strong></p>
                                                    <p class="mb-1">🕳️ Remaining:
                                                        <strong class="{{ $remaining < 100 ? 'text-danger' : 'text-success' }}">
                                                            {{ $remaining }} CPM
                                                        </strong>
                                                    </p>

                                                    <div class="progress mt-2" style="height: 10px;">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                             style="width: {{ $percent }}%;"
                                                             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">{{ $percent }}% Full</small>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-muted ms-3">No lines found for this location.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endforeach
            </div>

        </div>

        @push('scripts')
        @endpush
    @endif--}}
</x-default-layout>
