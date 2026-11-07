<x-default-layout>
    @section('title', 'Product Traceability')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('warehouse.traceability') }}
    @endsection

    @php
        $formatQty = fn ($value) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 3), '0'), '.');
    @endphp

    <div dir="rtl" lang="ar" class="d-flex flex-column gap-6">
        <header class="card bg-primary text-white">
            <div class="card-body p-6 p-lg-9">
                <div class="d-flex align-items-start gap-4">
                    <span class="symbol symbol-45px flex-shrink-0">
                        <span class="symbol-label bg-white bg-opacity-20">
                            <i class="fa-solid fa-route fs-3 text-white" aria-hidden="true"></i>
                        </span>
                    </span>
                    <div>
                        <h1 class="text-white fw-bold fs-2x mb-2">تقرير تتبّع المنتج</h1>
                        <p class="text-white opacity-75 fs-6 mb-0">
                            تتبّع المنتج من بيان الإدخال إلى بيانات الإخراج، وراجع رصيده حسب الدفعة والموقع والفترة.
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <section class="card" aria-labelledby="traceability-filters">
            <div class="card-body p-6">
                <h2 id="traceability-filters" class="fw-bold fs-4 mb-5">فلاتر التقرير</h2>

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
                    <div class="row g-4 align-items-end">
                        <div class="col-12 col-lg-6">
                            <label for="trace-product" class="required form-label">المنتج</label>
                            <select name="product_id" id="trace-product" class="form-select form-select-solid"
                                    data-search-url="{{ route('warehouses.traceability.products') }}" required>
                                <option></option>
                                @if($product)
                                    <option value="{{ $product->id }}" selected>
                                        {{ $product->name }} · {{ filled($product->barcode) ? $product->barcode : '#'.$product->id }}
                                    </option>
                                @endif
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label for="trace-from-date" class="form-label">من تاريخ</label>
                            <input name="from_date" id="trace-from-date" type="date" dir="ltr"
                                   value="{{ old('from_date', $filters['from_date'] ?? '') }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-6 col-lg-3">
                            <label for="trace-to-date" class="required form-label">إلى تاريخ</label>
                            <input name="to_date" id="trace-to-date" type="date" dir="ltr"
                                   value="{{ old('to_date', $filters['to_date']) }}" class="form-control form-control-solid" required>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="trace-customer" class="form-label">العميل</label>
                            <select name="customer_id" id="trace-customer" class="form-select form-select-solid"
                                    data-control="select2" data-placeholder="كل العملاء" data-allow-clear="true" data-dir="rtl">
                                <option></option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id', $filters['customer_id'] ?? null) == $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <label for="trace-warehouse" class="form-label">المستودع</label>
                            <select name="warehouse_id" id="trace-warehouse" class="form-select form-select-solid">
                                <option value="">كل المستودعات</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $filters['warehouse_id'] ?? null) == $warehouse->id)>
                                        {{ $warehouse->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <label for="trace-batch" class="form-label">رقم الدفعة</label>
                            <input name="batch_number" id="trace-batch" type="text" maxlength="255" placeholder="كل الدفعات"
                                   value="{{ old('batch_number', $filters['batch_number'] ?? '') }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                عرض التقرير
                            </button>
                            <a class="btn btn-light" href="{{ route('warehouses.traceability') }}">مسح</a>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        @if($product)
            <section aria-labelledby="traceability-balance">
                <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <div class="text-gray-600 fw-semibold mb-1" id="traceability-balance">ملخص الكميات</div>
                        <h2 class="fs-2 fw-bold mb-1">{{ $product->name }}</h2>
                        <div class="text-gray-600 d-flex gap-3 flex-wrap">
                            @if($product->barcode !== null && $product->barcode !== '')
                                <span>الباركود <bdi class="fw-semibold">{{ $product->barcode }}</bdi></span>
                            @endif
                            @if($product->UnitMeasure?->name)
                                <span>الوحدة <bdi class="fw-semibold">{{ $product->UnitMeasure->name }}</bdi></span>
                            @endif
                        </div>
                    </div>
                    <span class="badge badge-light fs-7 py-2 px-3">
                        @if(!empty($filters['from_date']))
                            <bdi>{{ $filters['from_date'] }}</bdi>&nbsp; إلى &nbsp;
                        @else
                            كامل السجل حتى &nbsp;
                        @endif
                        <bdi>{{ $filters['to_date'] }}</bdi>
                    </span>
                </div>

                <div class="row g-3">
                    @foreach([
                        ['label' => 'الرصيد الافتتاحي', 'key' => 'opening', 'color' => 'info'],
                        ['label' => 'الوارد خلال الفترة', 'key' => 'inbound', 'color' => 'success'],
                        ['label' => 'الصادر خلال الفترة', 'key' => 'outbound', 'color' => 'warning'],
                        ['label' => 'الرصيد الختامي', 'key' => 'closing', 'color' => 'primary'],
                    ] as $metric)
                        @php
                            $qty = $summary->{$metric['key'].'_qty'};
                            $otherQty = $summary->{$metric['key'].'_other_qty'};
                        @endphp
                        <div class="col-6 col-xl-3">
                            <div class="card h-100 border border-{{ $metric['color'] }} border-opacity-25">
                                <div class="card-body p-4 p-lg-5">
                                    <span class="badge badge-light-{{ $metric['color'] }} text-gray-800 mb-3">{{ $metric['label'] }}</span>
                                    <div class="fw-bold fs-2x lh-1 mb-3 {{ $qty < 0 ? 'text-danger' : 'text-gray-900' }}">
                                        <bdi>{{ $formatQty($qty) }}</bdi>
                                    </div>
                                    <div class="text-gray-600 fs-7">
                                        <bdi dir="ltr">Other Qty <span class="fw-semibold">{{ $formatQty($otherQty) }}</span></bdi>
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
                        <h2 id="traceability-movements" class="fw-bold fs-3 mb-1">سجل الحركات</h2>
                        <p class="text-gray-600 mb-0">أحدث الحركات أولًا، مرتبة حسب تاريخ البيان.</p>
                    </div>
                    <a class="btn btn-sm btn-light-primary"
                       href="{{ route('warehouses.traceability', array_merge($filters, ['format' => 'csv'])) }}">
                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                        تصدير CSV
                    </a>
                </div>

                @if($rows->isEmpty())
                    <div class="card-body pt-0">
                        <p class="bg-light rounded p-5 mb-0 text-gray-700">ما في حركات مطابقة للفلاتر خلال هذه الفترة.</p>
                    </div>
                @else
                    <div class="card-body px-4 px-lg-6 pt-0">
                        <p class="d-lg-none text-gray-600 fs-7 mb-2">مرّر الجدول أفقيًا لرؤية باقي التفاصيل.</p>
                        <div class="table-responsive" tabindex="0" role="region" aria-labelledby="traceability-movements">
                            <table class="table table-row-dashed table-row-gray-300 align-middle fs-6 gy-4 mb-0"
                                   aria-labelledby="traceability-movements">
                                <thead>
                                    <tr class="text-gray-700 fw-bold bg-light text-nowrap">
                                        <th scope="col" class="ps-4">التاريخ</th>
                                        <th scope="col">الحركة</th>
                                        <th scope="col"><bdi>Qty</bdi></th>
                                        <th scope="col" class="d-none d-lg-table-cell"><bdi>Other Qty</bdi></th>
                                        <th scope="col">مرجع البيان والحالة</th>
                                        <th scope="col">العميل</th>
                                        <th scope="col" class="pe-4">الموقع / الدفعة</th>
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
                                                    {{ $isInbound ? 'وارد' : 'صادر' }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <bdi class="fw-bold">{{ $formatQty($row->quantity) }}</bdi>
                                                <div class="d-lg-none text-gray-600 fs-8">
                                                    <bdi dir="ltr">Other Qty {{ $formatQty($row->other_quantity) }}</bdi>
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
                                                        الوارد
                                                        @if($inboundUrl)
                                                            <a href="{{ $inboundUrl }}"><bdi>{{ $inboundReference }}</bdi></a>
                                                        @else
                                                            <bdi>{{ $inboundReference }}</bdi>
                                                        @endif
                                                    </div>
                                                @endunless
                                                <div class="text-gray-600 fs-7"><bdi>{{ $row->status_name }}</bdi></div>
                                            </td>
                                            <td><bdi>{{ $row->customer_name ?? '—' }}</bdi></td>
                                            <td class="pe-4 text-nowrap">
                                                <bdi>{{ \App\Services\Inventory\ProductTraceabilityReport::location($row) ?: '—' }}</bdi>
                                                @if($row->batch_number !== null && $row->batch_number !== '')
                                                    <div class="text-gray-600 fs-7">الدفعة <bdi>{{ $row->batch_number }}</bdi></div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-4">
                            <span class="text-gray-600">
                                <bdi>{{ $rows->firstItem() }}–{{ $rows->lastItem() }}</bdi> من <bdi>{{ $rows->total() }}</bdi> حركة
                            </span>
                            <div dir="ltr">{{ $rows->links('pagination::bootstrap-5') }}</div>
                        </div>
                    </div>
                @endif
            </section>

            <p class="text-gray-600 fs-7 mb-0">
                الرصيد محسوب من الكميات الواردة مطروحًا منها الصادر المحتسب في المخزون، بما فيه الصادر قيد التدقيق أو الاعتماد.
                يُحسب رصيد Other Qty للبنود التي سُجّلت لها كمية إضافية في الوارد.
                يعتمد فلتر المستودع على موقع بند الوارد الحالي. يعرض السجل موقع الصرف المحفوظ عند توفره.
            </p>
        @else
            <div class="card">
                <div class="card-body text-center py-12">
                    <i class="fa-solid fa-box-open fs-2x text-primary mb-4" aria-hidden="true"></i>
                    <h2 class="fw-bold fs-4">اختر منتجًا لعرض سجلّه</h2>
                    <p class="text-gray-600 mb-0">ابحث باسم المنتج أو بالباركود.</p>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            $(function () {
                const product = $('#trace-product');
                product.select2({
                    dir: 'rtl',
                    width: '100%',
                    placeholder: 'ابحث باسم المنتج أو بالباركود',
                    language: {
                        searching: () => 'جارٍ البحث…',
                        loadingMore: () => 'جارٍ تحميل المزيد…',
                        noResults: () => 'لا توجد منتجات مطابقة',
                        errorLoading: () => 'تعذّر تحميل المنتجات',
                    },
                    ajax: {
                        url: product.data('search-url'),
                        dataType: 'json',
                        delay: 250,
                        data: params => ({q: params.term || '', page: params.page || 1}),
                    },
                });
            });
        </script>
    @endpush
</x-default-layout>
