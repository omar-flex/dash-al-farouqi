<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ 'بيان جمركي '.$outbound->outbound_number  }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        @media print {
            .row { display: flex !important; flex-wrap: nowrap !important; }
            .col-6, .col-4, .col-3, .col-12 { flex: 1 0 0% !important; max-width: 100% !important; padding: 0 2px !important; }
            .table { width: 100% !important; font-size: 11px !important; border-collapse: collapse !important; margin-bottom: 0 !important; page-break-inside: avoid !important; }
            th, td { padding: 3px 2px !important; border: 1px solid #aaa !important; text-align: center !important; vertical-align: middle !important; word-break: break-word !important; }
            .no-break { page-break-inside: avoid !important; }
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
            <img src="{{asset('assets/media/logos/default-dark-new.png')}}" alt="Right Logo" class="img-fluid"
                 style="max-width: 180px; max-height: 75px;">
        </div>
    </div>


    <div class="text-center mb-2">
        <h3>مركز جمرك عمان / قسم المستودعات العامة</h3>
        <h4>بوندد الشرقية - رقم البوندد (618)</h4>
        <h5 class="fw-bold">نموذج اخراج بيان جمركي</h5>
    </div>

    <table class="table table-bordered mt-3 text-center">
        <thead>
        <tr>
            <th>المعاملة الجمركيه</th>
            <th>تاريخها</th>
            <th>من بيان ايداع</th>
            <th>منظمة في مركز جمركي</th>
            <th>اسم المرسل</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ $outbound->outbound_number }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($outbound->manifest_date)->format('Y/m/d') }}</td>
            <td>{{ $outbound->EnterRequest->bound_number }}</td>
            <td>{{ $outbound->customs_entry_center }}</td>
            <td>{{ $outbound->EnterRequest->Customer->name }}</td>
        </tr>
        </tbody>
    </table>

    @if($outbound->manifest_type_number == 3 || $outbound->manifest_type_number == 8)
        @php
            $cars = $outbound->Cars instanceof \Illuminate\Support\Collection
                ? $outbound->Cars->values()
                : collect($outbound->Cars)->values();

            $carsCount = $cars->count();
            $col = 12;
            $tables = 1;

            if ($carsCount > 11 && $carsCount <= 22) {
                $col = 6; $tables = 2;
            } elseif ($carsCount > 22 && $carsCount <= 33) {
                $col = 4; $tables = 3;
            } elseif ($carsCount > 33) {
                $col = 3; $tables = 4;
            }

            $chunkedCars = $cars->chunk(ceil($carsCount / $tables));
            $rowNum = 1;
        @endphp

        <h5 class="mt-4 fw-bold">كما هو مصرح بالبيان </h5>
        <div class="row">
            @foreach($chunkedCars as $chunk)
                <div class="col-{{$col}}">
                    <table class="table table-bordered mt-2 text-center table-sm no-break">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>رصاص رقم</th>
                            <th>سيارات رقم</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($chunk as $car)
                            <tr>
                                <td>{{ $rowNum++ }}</td>
                                <td>{{ $car->seal_number }}</td>
                                <td>{{ $car->number }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @endif

    <div class="col-12 mt-2">
        <div>
            <span class="fw-bold"> ملاحظة :</span>
            <span class="mx-1 text-gray-600">وبعد تحميل البيان بتاريخ  </span>
            <span
                class="fw-bold">@isset($outbound->date)
                    {{\Illuminate\Support\Carbon::parse($outbound->date)->format('Y/m/d')}}
                @endisset</span>
            <span>  للبيان و مرفقاته وجدت</span>
            <span class="fw-bold">  مطابق </span>
        </div>
    </div>


    <div class="no-break">
        <div class="mt-3">
            <span class="fw-bold">وصف البضاعة:</span>
            <span class="mx-1 text-gray-600">{{$outbound->general_description_goods}}</span>
        </div>
        <table class="table border-0 mt-3 ">
            <thead>
            <tr>
                @if($outbound->EnterRequest->country_id)
                    <th class="border-0">المنشأ</th>
                @endif
                <th class="border-0">عدد الطرود</th>
                <th class="border-0">الوزن القائم</th>
                <th class="border-0">الوزن الصافي</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                @if($outbound->EnterRequest->country_id)
                    <td class="text-gray-600 border-0">{{$outbound->EnterRequest->Country->name}}</td>
                @endif
                <td class="text-gray-600 border-0"><span class="fw-bold mx-1"> ({{$outbound->quantity_packages}})</span>طرد</td>
                <td class="text-gray-600 border-0"><span class="fw-bold mx-1"> ({{$outbound->gross_weight}})</span>كغم</td>
                <td class="text-gray-600 border-0"><span class="fw-bold mx-1"> ({{$outbound->net_weight}})</span>كغم</td>
            </tr>
            </tbody>
        </table>
        <hr>
        <div class="mt-2">
            <span class="fw-bold">موقع التخزين :</span>
            <span class="mx-1 text-gray-600">تم التحميل من مستودع  </span>
            <span class="fw-bold">({{$outbound->EnterRequest->Warehouse?->code}})</span>
        </div>
        @if($outbound->notes)
            <div class="mt-3">
                <span class="fw-bold">ملاحظات اضافية:</span>
                <span class="mx-1 text-gray-600">{{$outbound->notes}}</span>
            </div>
        @endif
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4"
         style="width: 500px; margin:auto; margin-top:50px ">
        <h3 class="fw-bold">م . دائرة الجمارك</h3>
        <h3 class="fw-bold">م . الهيئة المستثمرة</h3>
    </div>


</div>
</body>
</html>
