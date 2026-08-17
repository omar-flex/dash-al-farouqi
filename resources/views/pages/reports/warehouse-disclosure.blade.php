<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> ﻛﺸﻒ ﺍﻟﻤﺴﺘﻮﺩﻉ {{isset($warehouse) ? $warehouse->code : ''}}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .odd {
            background-color: rgba(114, 57, 234, 0.35) !important;
        }

        .over-allocated {
            background-color: #f8d7da !important;
            color: #842029 !important;
            font-weight: bold;
        }
    </style>
</head>
<body class="container container-xxl mt-2" style="max-width: 1800px">
<div class="card p-2 border-0">
    <div class="row">
        <div class="col-4 text-end" style="margin: auto">
            <img src="{{asset('assets/media/logos/img.png')}}" alt="Left Logo" class="img-fluid"
                 style="max-width: 180px; max-height: 75px;">
        </div>
        <div class="col-4 text-center">
            <div class="text-center mb-2">
                <h3 class="mt-2">دائرة الجمارك الأردنية</h3>
            </div>
        </div>
        <div class="col-4 text-start" style="margin: auto">
            <img src="{{asset('assets/media/logos/default-dark.png')}}" alt="Right Logo" class="img-fluid"
                 style="max-width: 180px; max-height: 75px;">
        </div>
    </div>


    <div class="text-center mb-2">
        <h3>مركز جمرك عمان / قسم المستودعات العامة</h3>
        <h4>بوندد الشرقية - رقم البوندد (618)</h4>
        <h5 class="fw-bold"> ﻛﺸﻒ ﺍﻟﻤﺴﺘﻮﺩﻉ {{isset($warehouse) ? $warehouse->code : ''}}</h5>

    </div>

    <div style="text-align: center; margin-bottom: 20px;">
        <p><strong>الفترة:</strong> من {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }}
            إلى {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</p>
    </div>

    <table class="table table-bordered mt-3 text-center">
        <thead>
        <tr>
            <th>تسلسل</th>
            <th>رقم البيان</th>
            <th>ﻭﺻﻒ ﺍﻟﺒﻀﺎﻋﺔ</th>
            <th>ﺍﻟﻜﻤﻴﺔ</th>
            <th>ﺍﻟﻤﺘﺒﻘﻲ</th>
            <th>القيمة الجمركية</th>
            <th>الوزن الإجمالي</th>
            <th>ﺕ.ﺇﺩﺧﺎﻝ</th>
            <th>ﺕ.ﺇﺧﺮﺍﺝ</th>
            <th>ﺕ.ﺇﻧﺘﻬﺎﺀ</th>
            <th>الرقم</th>
            <th>نوع البيان</th>
            <th>سنة</th>
            <th>مركز</th>
        </tr>
        </thead>
        <tbody>
        @foreach($customerGroups as $customer)
            <tr>
                <td colspan="14" style="background: #0B0C10; color: #fff; font-size: 20px">
                    {{ $customer->name }} - {{ $customer->tax_number }}
                </td>
            </tr>

            <tr style="background: #eaeaea; font-weight: bold;">
                <td colspan="3"></td>
                <td class="text-danger">{{ $customer->total_quantity }}</td>
                <td class="text-danger">{{ $customer->total_remaining }}</td>
                <td class="text-danger">{{ number_format($customer->total_custom_value, 2) }}</td>
                <td class="text-danger">{{ number_format($customer->total_gross_weight, 2) }}</td>
                <td colspan="7"></td>
            </tr>

            @foreach($customer->items as $item)
                @php
                    $loopClass = $item->remaining_quantity < 0
                        ? 'over-allocated'
                        : ($loop->iteration % 2 === 0 ? 'odd' : '');
                @endphp
                <tr>
                    <td class="{{ $loopClass }}">{{ $loop->iteration }}</td>
                    <td class="{{ $loopClass }}">{{ $item->bound_number }}</td>
                    <td class="{{ $loopClass }}" style="font-size:10px" width="400px">
                        {{ $item->product_name }} - {{ $item->product_barcode }} - {{ $item->batch_number }}
                    </td>
                    <td class="{{ $loopClass }}">{{ $item->quantity }}</td>
                    <td class="{{ $loopClass }}">
                        {{ $item->remaining_quantity }}
                        @if($item->remaining_quantity < 0)
                            <span title="صرف زائد">⚠</span>
                        @endif
                    </td>
                    <td class="{{ $loopClass }}">{{ number_format($item->remaining_custom_value, 2) }}</td>
                    <td class="{{ $loopClass }}">{{ number_format($item->remaining_gross_weight, 2) }}</td>
                    <td class="{{ $loopClass }}">
                        {{ \Carbon\Carbon::parse($item->inbound_date)->format('d/m/Y') }}
                    </td>
                    <td class="{{ $loopClass }}">
                        {{ $item->last_outbound_date
                            ? \Carbon\Carbon::parse($item->last_outbound_date)->format('d/m/Y')
                            : '-' }}
                    </td>
                    <td class="{{ $loopClass }}">
                        {{ \Carbon\Carbon::parse($item->inbound_date)->addYears(3)->format('d/m/Y') }}
                    </td>
                    <td class="{{ $loopClass }}">{{ $item->manifest_bound_number }}</td>
                    <td class="{{ $loopClass }}">{{ $item->manifest_type_number }}</td>
                    <td class="{{ $loopClass }}">{{ $item->manifest_year }}</td>
                    <td class="{{ $loopClass }}">{{ $item->customs_entry_center }}</td>
                </tr>
            @endforeach

            <tr style="background: #eaeaea; font-weight: bold;">
                <td colspan="3"></td>
                <td class="text-danger">{{ $customer->total_quantity }}</td>
                <td class="text-danger">{{ $customer->total_remaining }}</td>
                <td class="text-danger">{{ number_format($customer->total_custom_value, 2) }}</td>
                <td class="text-danger">{{ number_format($customer->total_gross_weight, 2) }}</td>
                <td colspan="7"></td>
            </tr>
        @endforeach


        </tbody>
    </table>


</div>
</body>
</html>
