<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ 'بيان جمركي '.$enterRequest->bound_number  }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        @media print {
            body, .container, .container-xxl, .card {
                width: 100% !important;
                max-width: 100% !important;
                background: #fff !important;
                color: #000 !important;
            }

            .row {
                display: flex !important;
                flex-wrap: nowrap !important;
                margin-right: 0 !important;
                margin-left: 0 !important;
            }

            .col-6, .col-4, .col-3 {
                flex: 1 0 0% !important;
                max-width: 100% !important;
                padding: 0 2px !important;
            }

            table.table {
                width: 100% !important;
                font-size: 11px !important;
                border-collapse: collapse !important;
                page-break-inside: avoid !important;
                margin-bottom: 3 !important;
            }

            th, td {
                padding: 3px 2px !important;
                border: 1px solid #aaa !important;
                text-align: center !important;
                vertical-align: middle !important;
                word-break: break-word !important;
            }

            thead th {
                background: #f7f7f7 !important;
                font-weight: bold;
            }

            h3, h4, h5 {
                margin: 4px 0 4px 0 !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }


            hr {
                margin: 10px 0 !important;
            }

            img, h3, h4, h5 {
                page-break-inside: avoid !important;
            }

            .no-print, .btn, nav, .print-hide {
                display: none !important;
            }
        }

    </style>
</head>
<body class="container container-xxl mt-2" style="max-width: 1860px">
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

    <div class="text-center mb-5">
        <h3>مركز جمرك عمان / قسم المستودعات العامة</h3>
        <h4>بوندد الشرقية - رقم البوندد (618)</h4>
        <h5 class="fw-bold">نموذج ايداع بيان جمركي</h5>
    </div>


    <table class="table table-bordered mt-2 text-center table-sm">
        <thead>
        <tr>
            <th>رقم البيان</th>
            <th>التاريخ</th>
            <th>مركز التنظيم</th>
            <th>اسم المرسل إليه</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ $enterRequest->bound_number }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($enterRequest->manifest_date)->format('Y/m/d') }}</td>
            <td>{{ $enterRequest->customs_entry_center }}</td>
            <td>{{ $enterRequest->Customer->name }}</td>
        </tr>

        </tbody>
    </table>
    <h5 class="mt-2 fw-bold">كما هو مصرح بالبيان </h5>
    @php
        $cars = $enterRequest->Cars instanceof \Illuminate\Support\Collection
            ? $enterRequest->Cars->values()
            : collect($enterRequest->Cars)->values();

        $carsCount = $cars->count();
        $col = 12;
        $tables = 1;

        if ($carsCount > 11 && $carsCount <= 22) {
            $col = 6;
            $tables = 2;
        } elseif ($carsCount > 22 && $carsCount <= 33) {
            $col = 4;
            $tables = 3;
        } elseif ($carsCount > 33) {
            $col = 3;
            $tables = 4;
        }

        $chunkedCars = $cars->chunk(ceil($carsCount / $tables));
        $rowNum = 1; // متغير عداد الصفوف
    @endphp

    <div class="row">
        @foreach($chunkedCars as $chunk)
            <div class="col-{{$col}}">
                <table class="table table-bordered mt-2 text-center table-sm">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>رصاص رقم</th>
                        <th>سيارات رقم</th>
                        <th>سليم/غير سليم</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($chunk as $car)
                        <tr>
                            <td>{{ $rowNum++ }}</td>
                            <td>{{ $car->seal_number ?? 'NONE' }}</td>
                            <td>{{ $car->number }}</td>
                            <td>{{ $car->is_status ? 'سليم' : 'غير سليم' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>


    <div class="no-print-break">
        <div class="mt-2">
            <span class="fw-bold">وصف البضاعة:</span>
            <span class="mx-1 text-gray-600">{{$enterRequest->general_description_goods}}</span>
        </div>
        <table class="table border-0 mt-2 table-sm mb-4" style="width: 350px;">
            <thead>
            <tr>
                @if($enterRequest->country_id)
                    <th class="border-0">المنشأ</th>
                @endif
                <th class="border-0">عدد الطرود</th>
                <th class="border-0">الوزن القائم</th>
                <th class="border-0">الوزن الصافي</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                @if($enterRequest->country_id)
                    <td class=" text-gray-600 border-0">{{$enterRequest->Country->name}}</td>
                @endif
                <td class=" text-gray-600 border-0"><span
                        class="fw-bold mx-1"> ({{$enterRequest->quantity_packages}})</span>طرد
                </td>
                <td class=" text-gray-600 border-0">
                    <span class="fw-bold mx-1"> ({{$enterRequest->gross_weight}})</span>كغم
                </td>
                <td class=" text-gray-600 border-0">
                    <span class="fw-bold mx-1"> ({{$enterRequest->net_weight}})</span>كغم
                </td>
            </tr>
            </tbody>
        </table>
        <hr>
        <div class="mt-2">
            <span class="fw-bold"> ملاحظة :</span>
            <span class="mx-1 text-gray-600">عند ادخال المحتويات بتاريخ  </span>
            <span class="fw-bold">@isset($enterRequest->date)
                    {{\Illuminate\Support\Carbon::parse($enterRequest->date)->format('Y/m/d')}}
                @endisset</span>
            <span>  تبين مايلي</span>
            <span class="fw-bold">  مطابق </span>
        </div>
        <div class="mt-2">
            <span class="fw-bold"> موقع التخزين :</span>
            <span class="mx-1 text-gray-600">تم التنزيل في مستودع  </span>
            <span class="fw-bold">({{$enterRequest->Warehouse?->code}})</span>
        </div>
        @if($enterRequest->notes)
            <div class="mt-2">
                <span class="fw-bold">ملاحظات اضافية:</span>
                <span class="mx-1 text-gray-600">{{$enterRequest->notes}}</span>
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
