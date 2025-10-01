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
        @foreach($customers as $customer)
            @php
                $validInbounds = $customer->getInbounds()->filter(function ($inbound) {
                    return $inbound->WarehouseItems->contains(function ($item) {
                        return $item->quantity - $item->SumOutboundItems() > 0;
                    });
                });
            @endphp

            @if($validInbounds->count() > 0)
                <tr>
                    <td colspan="14" style="background: #0B0C10; color: #fff; font-size: 20px">
                        {{$customer->name}} - {{$customer->tax_number}}
                    </td>
                </tr>
                @php
                    $count = 1;
                    $totalQuantity = 0;
                    $totalRemaining = 0;
                    $totalCustomValue = 0;
                    $totalGrossWeight = 0;
                @endphp

                @foreach($validInbounds as $inbound)
                    @foreach($inbound->WarehouseItems as $item)
                        @php
                            $remaining = $item->quantity - $item->SumOutboundItems();
                            $remaining_custom_value =  $item->custom_value - $item->SumOutboundCustomValue();
                            $remaining_cross_weight =  $item->gross_weight - $item->SumOutboundGrossWeight()
                        @endphp
                        @if($remaining > 0)
                            @php
                                $totalQuantity += $item->quantity;
                                $totalRemaining += $remaining;
                                $totalCustomValue += $remaining_custom_value;
                                $totalGrossWeight += $remaining_cross_weight;
                            @endphp
                        @endif
                    @endforeach
                @endforeach


                <tr style="background: #eaeaea; font-weight: bold;">
                    <td colspan="3"></td>
                    <td class="text-danger">{{ $totalQuantity }}</td>
                    <td class="text-danger">{{ $totalRemaining }}</td>
                    <td class="text-danger"> {{ number_format($totalCustomValue ,2)}}</td>
                    <td class="text-danger"> {{ number_format($totalGrossWeight ,2)}}</td>
                    <td colspan="6"></td>
                </tr>

                @foreach($validInbounds as $inbound)
                    @foreach($inbound->WarehouseItems as $item)
                        @if(($item->quantity - $item->SumOutboundItems()) > 0)
                            @php
                                $loopClass = $count % 2 == 0 ? 'odd' : '';
                                $remaining = $item->quantity - $item->SumOutboundItems();
                                $remaining_custom_value =  $item->custom_value - $item->SumOutboundCustomValue();
                                $remaining_cross_weight =  $item->gross_weight - $item->SumOutboundGrossWeight();
                            @endphp
                            <tr>
                                <td class="{{ $loopClass }}">{{$count}}</td>
                                <td class="{{ $loopClass }}">{{ $item->EnterRequest->bound_number }}</td>
                                <td class="{{ $loopClass }}" style="font-size:10px" width="400px">
                                    {{ $item->Product->name }} - {{ $item->Product->barcode }}
                                    - {{ $item->batch_number }}
                                </td>
                                <td class="{{ $loopClass }}">{{ $item->quantity }}</td>
                                <td class="{{ $loopClass }}">{{$remaining}}</td>
                                <td class="{{ $loopClass }}">{{number_format($remaining_custom_value,2)}}</td>
                                <td class="{{ $loopClass }}">{{number_format($remaining_cross_weight,2)}}</td>
                                <td class="{{ $loopClass }}">
                                    {{ \Carbon\Carbon::parse($item->EnterRequest->date)->format('d/m/Y') }}
                                </td>
                                <td class="{{ $loopClass }}">
                                    {{ optional($item->EnterRequest->LastOutbound())->date
                                        ? \Carbon\Carbon::parse($item->EnterRequest->LastOutbound()->date)->format('d/m/Y')
                                        : '-' }}
                                </td>
                                <td class="{{ $loopClass }}">
                                    {{ \Carbon\Carbon::parse($item->EnterRequest->date)->addYears(3)->format('d/m/Y') }}
                                </td>
                                <td class="{{ $loopClass }}">{{ $item->EnterRequest->manifest_bound_number }}</td>
                                <td class="{{ $loopClass }}">{{ $item->EnterRequest->manifest_type_number }}</td>
                                <td class="{{ $loopClass }}">{{ $item->EnterRequest->manifest_year }}</td>
                                <td class="{{ $loopClass }}">{{ $item->EnterRequest->customs_entry_center }}</td>
                            </tr>
                            @php ++$count; @endphp
                        @endif
                    @endforeach
                @endforeach
                <tr style="background: #eaeaea; font-weight: bold;">
                    <td colspan="3"></td>
                    <td class="text-danger">{{ $totalQuantity }}</td>
                    <td class="text-danger">{{ $totalRemaining }}</td>
                    <td class="text-danger"> {{ number_format($totalCustomValue ,2)}}</td>
                    <td colspan="6"></td>
                </tr>
            @endif
        @endforeach


        </tbody>
    </table>


</div>
</body>
</html>
