<x-default-layout>
    @section('title', __('product_traceability.title'))

    @section('breadcrumbs')
        {{ Breadcrumbs::render('warehouse.traceability') }}
    @endsection

    @php
        $direction = app()->isLocale('ar') ? 'rtl' : 'ltr';
        $selectedCustomer = $product ? $filters['customer_id'] : old('customer_id', $filters['customer_id'] ?? null);
        $formatQty = fn ($value) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 3), '0'), '.');
    @endphp

    <div dir="{{ $direction }}" class="d-flex flex-column gap-6">
        <header class="card bg-primary text-white">
            <div class="card-body p-6 p-lg-9">
                <div class="d-flex align-items-start gap-4">
                    <span class="symbol symbol-45px flex-shrink-0">
                        <span class="symbol-label bg-white bg-opacity-20">
                            <i class="fa-solid fa-route fs-3 text-white" aria-hidden="true"></i>
                        </span>
                    </span>
                    <div>
                        <h1 class="text-white fw-bold fs-2x mb-2">{{ __('product_traceability.title') }}</h1>
                        <p class="text-white opacity-75 fs-6 mb-0">
                            {{ __('product_traceability.intro') }}
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <section class="card" aria-labelledby="traceability-filters">
            <div class="card-body p-6">
                <h2 id="traceability-filters" class="fw-bold fs-4 mb-5">{{ __('product_traceability.filters') }}</h2>

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('warehouses.traceability') }}" method="get">
                    <div class="row g-4">
                        <div class="col-12 col-md-5">
                            <label for="trace-customer" class="required form-label">{{ __('product_traceability.customer') }}</label>
                            <select name="customer_id" id="trace-customer" class="form-select form-select-solid" required
                                    data-control="select2" data-placeholder="{{ __('product_traceability.choose_customer') }}"
                                    data-allow-clear="true" data-dir="{{ $direction }}">
                                <option></option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected($selectedCustomer == $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-7">
                            <label for="trace-product" class="required form-label">{{ __('product_traceability.product') }}</label>
                            <select name="product_id" id="trace-product" class="form-select form-select-solid"
                                    data-search-url="{{ route('warehouses.traceability.products') }}"
                                    aria-describedby="trace-customer-help" required @disabled(!$selectedCustomer)>
                                <option></option>
                                @if($product)
                                    <option value="{{ $product->id }}" selected>
                                        {{ $product->name }} · {{ filled($product->barcode) ? $product->barcode : '#'.$product->id }}
                                    </option>
                                @endif
                            </select>
                            <p class="text-gray-600 fs-7 mt-1 mb-0" id="trace-customer-help" @if($selectedCustomer) hidden @endif>
                                {{ __('product_traceability.customer_first') }}
                            </p>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="trace-from-date" class="form-label">{{ __('product_traceability.from_date') }}</label>
                            <input name="from_date" id="trace-from-date" type="date" dir="ltr"
                                   value="{{ old('from_date', $filters['from_date'] ?? '') }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="trace-to-date" class="required form-label">{{ __('product_traceability.to_date') }}</label>
                            <input name="to_date" id="trace-to-date" type="date" dir="ltr"
                                   value="{{ old('to_date', $filters['to_date']) }}" class="form-control form-control-solid" required>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="trace-warehouse" class="form-label">{{ __('product_traceability.warehouse') }}</label>
                            <select name="warehouse_id" id="trace-warehouse" class="form-select form-select-solid">
                                <option value="">{{ __('product_traceability.all_warehouses') }}</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $filters['warehouse_id'] ?? null) == $warehouse->id)>
                                        {{ $warehouse->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="trace-batch" class="form-label">{{ __('product_traceability.batch_number') }}</label>
                            <input name="batch_number" id="trace-batch" type="text" maxlength="255"
                                   placeholder="{{ __('product_traceability.all_batches') }}"
                                   value="{{ old('batch_number', $filters['batch_number'] ?? '') }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-12 col-lg-4 align-self-end d-flex flex-wrap gap-2">
                            <button class="btn btn-primary flex-grow-1" id="trace-run" type="submit" @disabled(!$product)>
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                {{ __('product_traceability.run') }}
                            </button>
                            <a class="btn btn-light" href="{{ route('warehouses.traceability') }}">{{ __('product_traceability.reset') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        @if($product)
            <div id="trace-results">
                <div class="d-flex flex-column gap-6">
                    <section aria-labelledby="traceability-balance">
                        <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-4">
                            <div>
                                <div class="text-gray-600 fw-semibold mb-1" id="traceability-balance">{{ __('product_traceability.summary') }}</div>
                                <h2 class="fs-2 fw-bold mb-1">{{ $product->name }}</h2>
                                <div class="text-gray-600 d-flex gap-3 flex-wrap">
                                    @if($product->barcode !== null && $product->barcode !== '')
                                        <span>{{ __('product_traceability.barcode') }} <bdi class="fw-semibold">{{ $product->barcode }}</bdi></span>
                                    @endif
                                    @if($product->UnitMeasure?->name)
                                        <span>{{ __('product_traceability.unit') }} <bdi class="fw-semibold">{{ $product->UnitMeasure->name }}</bdi></span>
                                    @endif
                                </div>
                            </div>
                            <span class="badge badge-light fs-7 py-2 px-3">
                                @if(!empty($filters['from_date']))
                                    <bdi>{{ $filters['from_date'] }}</bdi>&nbsp; {{ __('product_traceability.through') }} &nbsp;
                                @else
                                    {{ __('product_traceability.all_history') }} &nbsp;
                                @endif
                                <bdi>{{ $filters['to_date'] }}</bdi>
                            </span>
                        </div>

                        <div class="row g-3">
                            @foreach([
                                ['key' => 'opening', 'color' => 'info'],
                                ['key' => 'inbound', 'color' => 'success'],
                                ['key' => 'outbound', 'color' => 'warning'],
                                ['key' => 'closing', 'color' => 'primary'],
                            ] as $metric)
                                @php
                                    $qty = $summary->{$metric['key'].'_qty'};
                                    $otherQty = $summary->{$metric['key'].'_other_qty'};
                                @endphp
                                <div class="col-6 col-xl-3">
                                    <div class="card h-100 border border-{{ $metric['color'] }} border-opacity-25">
                                        <div class="card-body p-4 p-lg-5">
                                            <span class="badge badge-light-{{ $metric['color'] }} text-gray-800 mb-3">{{ __('product_traceability.'.$metric['key']) }}</span>
                                            <div class="fw-bold fs-2x lh-1 mb-3 {{ $qty < 0 ? 'text-danger' : 'text-gray-900' }}">
                                                <bdi>{{ $formatQty($qty) }}</bdi>
                                            </div>
                                            <div class="text-gray-600 fs-7">
                                                <bdi dir="{{ $direction }}">{{ __('product_traceability.other_qty') }} <span class="fw-semibold">{{ $formatQty($otherQty) }}</span></bdi>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="card" aria-labelledby="traceability-movements">
                        <div class="card-header border-0 align-items-center py-4 gap-3">
                            <div>
                                <h2 id="traceability-movements" class="fw-bold fs-3 mb-1">{{ __('product_traceability.movements') }}</h2>
                                <p class="text-gray-600 mb-0">{{ __('product_traceability.movement_order') }}</p>
                            </div>
                            <a class="btn btn-sm btn-light-primary"
                               href="{{ route('warehouses.traceability', array_merge($filters, ['format' => 'csv'])) }}">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                {{ __('product_traceability.export') }}
                            </a>
                        </div>

                        @if($rows->isEmpty())
                            <div class="card-body pt-0">
                                <p class="bg-light rounded p-5 mb-0 text-gray-700">{{ __('product_traceability.empty') }}</p>
                            </div>
                        @else
                            <div class="card-body px-4 px-lg-6 pt-0">
                                <p class="d-lg-none text-gray-600 fs-7 mb-2">{{ __('product_traceability.scroll') }}</p>
                                <div class="table-responsive" tabindex="0" role="region" aria-labelledby="traceability-movements">
                                    <table class="table table-row-dashed table-row-gray-300 align-middle fs-6 gy-4 mb-0"
                                           aria-labelledby="traceability-movements">
                                        <thead>
                                            <tr class="text-gray-700 fw-bold bg-light text-nowrap">
                                                <th scope="col" class="ps-4">{{ __('product_traceability.date') }}</th>
                                                <th scope="col">{{ __('product_traceability.movement') }}</th>
                                                <th scope="col"><bdi>{{ __('product_traceability.qty') }}</bdi></th>
                                                <th scope="col" class="d-none d-lg-table-cell"><bdi>{{ __('product_traceability.other_qty') }}</bdi></th>
                                                <th scope="col">{{ __('product_traceability.document') }}</th>
                                                <th scope="col">{{ __('product_traceability.customer') }}</th>
                                                <th scope="col" class="pe-4">{{ __('product_traceability.location_batch') }}</th>
                                            </tr>
                                        </thead>
                                        @php
                                            $canOpenInbound = auth()->user()->can('list_enter_requests');
                                            $canOpenOutbound = auth()->user()->can('list_outbounds');
                                        @endphp
                                        <tbody>
                                            @foreach($rows as $row)
                                                @php
                                                    $isInbound = (int) $row->direction === 1;
                                                    $inboundReference = $row->inbound_reference ?: '#'.$row->inbound_id;
                                                    $inboundUrl = $canOpenInbound ? route('operation-management.enter_requests.show', $row->inbound_id) : null;
                                                    $reference = $isInbound ? $inboundReference : ($row->outbound_reference ?: '#'.$row->outbound_id);
                                                    $documentUrl = $isInbound ? $inboundUrl : ($canOpenOutbound ? route('operation-management.outbounds.show', $row->outbound_id) : null);
                                                @endphp
                                                <tr>
                                                    <td class="ps-4 text-nowrap"><bdi>{{ $row->movement_date }}</bdi></td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $isInbound ? 'success' : 'warning' }} text-gray-800">
                                                            {{ __($isInbound ? 'product_traceability.received' : 'product_traceability.released') }}
                                                        </span>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <bdi class="fw-bold">{{ $formatQty($row->quantity) }}</bdi>
                                                        <div class="d-lg-none text-gray-600 fs-8">
                                                            <bdi dir="{{ $direction }}">{{ __('product_traceability.other_qty') }} {{ $formatQty($row->other_quantity) }}</bdi>
                                                        </div>
                                                    </td>
                                                    <td class="d-none d-lg-table-cell text-nowrap"><bdi>{{ $formatQty($row->other_quantity) }}</bdi></td>
                                                    <td class="text-nowrap">
                                                        @if($documentUrl)
                                                            <a class="fw-bold" href="{{ $documentUrl }}"><bdi>{{ $reference }}</bdi></a>
                                                        @else
                                                            <bdi class="fw-bold">{{ $reference }}</bdi>
                                                        @endif
                                                        @unless($isInbound)
                                                            <div class="text-gray-600 fs-7">
                                                                {{ __('product_traceability.received') }}
                                                                @if($inboundUrl)
                                                                    <a href="{{ $inboundUrl }}"><bdi>{{ $inboundReference }}</bdi></a>
                                                                @else
                                                                    <bdi>{{ $inboundReference }}</bdi>
                                                                @endif
                                                            </div>
                                                        @endunless
                                                        <div class="text-gray-600 fs-7"><bdi>{{ __('product_traceability.statuses.'.$row->status_id) }}</bdi></div>
                                                    </td>
                                                    <td><bdi>{{ $row->customer_name ?? '—' }}</bdi></td>
                                                    <td class="pe-4 text-nowrap">
                                                        <bdi>{{ \App\Services\Inventory\ProductTraceabilityReport::location($row) ?: '—' }}</bdi>
                                                        @if($row->batch_number !== null && $row->batch_number !== '')
                                                            <div class="text-gray-600 fs-7">{{ __('product_traceability.batch') }} <bdi>{{ $row->batch_number }}</bdi></div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-4">
                                    <span class="text-gray-600">
                                        {{ __('product_traceability.row_count', ['first' => $rows->firstItem(), 'last' => $rows->lastItem(), 'total' => $rows->total()]) }}
                                    </span>
                                    <div dir="ltr">{{ $rows->links('pagination::bootstrap-5') }}</div>
                                </div>
                            </div>
                        @endif
                    </section>

                    <p class="text-gray-600 fs-7 mb-0">
                        {{ __('product_traceability.balance_note') }}
                    </p>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body text-center py-12">
                    <i class="fa-solid fa-box-open fs-2x text-primary mb-4" aria-hidden="true"></i>
                    <h2 class="fw-bold fs-4">{{ __('product_traceability.start_title') }}</h2>
                    <p class="text-gray-600 mb-0">{{ __('product_traceability.start_help') }}</p>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            $(function () {
                const customer = $('#trace-customer');
                const product = $('#trace-product');
                const run = $('#trace-run');

                product.select2({
                    dir: @js($direction),
                    width: '100%',
                    placeholder: @js(__('product_traceability.search_product')),
                    language: {
                        searching: () => @js(__('product_traceability.searching')),
                        loadingMore: () => @js(__('product_traceability.loading_more')),
                        noResults: () => @js(__('product_traceability.no_results')),
                        errorLoading: () => @js(__('product_traceability.search_error')),
                    },
                    ajax: {
                        url: product.data('search-url'),
                        dataType: 'json',
                        delay: 250,
                        data: params => ({customer_id: customer.val(), q: params.term || '', page: params.page || 1}),
                    },
                });

                product.on('change', () => run.prop('disabled', !product.val()));
                customer.on('change', function () {
                    product.select2('close');
                    product.empty().append(new Option('', '')).prop('disabled', !this.value).trigger('change');
                    $('#trace-customer-help').prop('hidden', Boolean(this.value));
                    $('#trace-results').prop('hidden', true);
                });
            });
        </script>
    @endpush
</x-default-layout>
