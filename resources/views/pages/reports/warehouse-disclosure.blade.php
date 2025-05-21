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
        <p><strong>الفترة:</strong> من {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} إلى {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</p>
    </div>

    <table class="table table-bordered mt-3 text-center">
        <thead>
        <tr>
            <th>تسلسل</th>
            <th>رقم البيان</th>
            <th>ﻭﺻﻒ ﺍﻟﺒﻀﺎﻋﺔ</th>
            <th>ﺍﻟﻜﻤﻴﺔ</th>
            <th>ﺍﻟﻤﺘﺒﻘﻲ</th>
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
                    <td colspan="12" style="background: #0B0C10; color: #fff; font-size: 20px">
                        {{$customer->name}} - {{$customer->tax_number}}
                    </td>
                </tr>
                @php $count = 1; @endphp

                @foreach($validInbounds as $inbound)
                    @foreach($inbound->WarehouseItems as $item)
                        @if($item->quantity - $item->SumOutboundItems() > 0)
                            @php $loop = $count % 2 == 0; @endphp
                            <tr>
                                <td @if($loop) class="odd" @endif >{{$count}}</td>
                                <td @if($loop) class="odd" @endif >{{ $item->EnterRequest->bound_number }}</td>
                                <td @if($loop) class="odd" @endif style="font-size:10px" width="400px">
                                    {{ $item->Product->name }} -  {{ $item->Product->barcode }} -  {{ $item->batch_number }}
                                </td>
                                <td @if($loop) class="odd" @endif >{{ $item->quantity }}</td>
                                <td @if($loop) class="odd" @endif >
                                    {{$item->quantity - $item->SumOutboundItems()}}
                                </td>
                                <td @if($loop) class="odd" @endif >
                                    {{ \Carbon\Carbon::parse($item->EnterRequest->date)->format('d/m/Y') }}
                                </td>
                                <td @if($loop) class="odd" @endif >
                                    {{ optional($item->EnterRequest->LastOutbound())->date
                                        ? \Carbon\Carbon::parse($item->EnterRequest->LastOutbound()->date)->format('d/m/Y')
                                        : '-' }}
                                </td>
                                <td @if($loop) class="odd" @endif >
                                    {{ \Carbon\Carbon::parse($item->EnterRequest->date)->addYears(3)->format('d/m/Y') }}
                                </td>
                                <td @if($loop) class="odd" @endif >
                                    {{ $item->EnterRequest->manifest_bound_number }}
                                </td>
                                <td @if($loop) class="odd" @endif >
                                    {{ $item->EnterRequest->manifest_type_number }}
                                </td>
                                <td @if($loop) class="odd" @endif >
                                    {{ $item->EnterRequest->manifest_year }}
                                </td>
                                <td @if($loop) class="odd" @endif >
                                    {{ $item->EnterRequest->customs_entry_center }}
                                </td>
                            </tr>
                            @php ++$count; @endphp
                        @endif
                    @endforeach
                @endforeach
            @endif
        @endforeach


        </tbody>
    </table>


</div>
</body>
</html>
