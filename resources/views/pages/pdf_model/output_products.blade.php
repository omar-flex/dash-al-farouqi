<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ 'نموذج إخراج بضاعة '. " ($car_id) " . $outbound->outbound_number  }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
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
        <h5 class="fw-bold">نموذج إخراج بضاعة</h5>
    </div>

    <table class="table table-bordered mt-3 text-center">
        <thead>
        <tr>
            <th>اسم الشركة المالكة للبضاعة (الجهة الخاصة)</th>
            <th>رقم الايداع</th>
            <th>الرقم الضريبي</th>
            <th>رقم البيان الجمركي</th>
            <th>تاريخ البيان</th>
            <th>اسم شركة التخليص</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ $outbound->EnterRequest?->Customer?->name }}</td>
            <td>{{ $outbound->EnterRequest?->bound_number }}</td>
            <td>{{ $outbound->EnterRequest?->Customer?->tax_number }}</td>
            <td>{{ $outbound->outbound_number }}</td>
            <td>{{\Illuminate\Support\Carbon::parse($outbound->manifest_date)->format('Y/m/d')}}</td>
            <td>{{ $outbound->EnterRequest?->Company?->name }}</td>
        </tr>
        </tbody>
    </table>

    <h4 class="mb-2 fw-bold" style="color:#0c5cc3">تفاصيل البضاعة:</h4>

    <table class="table table-bordered mt-3 text-center">
        <thead>
        <tr>
            <th>رقم</th>
            <th>وصف الصنف</th>
            <th>نوع العبوة</th>
            <th> الكمية</th>
            <th>الوزن الإجمالي</th>
            <th>الوزن الصافي</th>
        </tr>
        </thead>
        <tbody>
        @foreach($items as $key => $item)
            <tr>
                <td>{{ ++$key }}</td>
                <td>{{$item->WarehouseItem?->Product?->name}}</td>
                <td>{{ $item->WarehouseItem->Product?->UnitMeasure?->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->gross_weight }}</td>
                <td>{{ $item->net_weight }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h4 class="mb-2 fw-bold" style="color:#0c5cc3">بيانات الإخراج:</h4>
    <div>
        <div class="mb-2"> نوع وسيلة النقل: شاحنة / حاوية / أخرى</div>
        <div class="mb-2">رقم وسيلة النقل: ....................................................</div>
        <div class="mb-2">اسم السائق: ....................................................</div>
        <div class="mb-2">رقم هوية السائق: ....................................................</div>
        <div class="mb-2">
            <span> تاريخ الإخراج: </span>
            <span class="mx-2"> {{\Illuminate\Support\Carbon::parse($outbound->date)->format('Y/m/d')}} </span>
        </div>
        <div class="mb-2">
            <span> سبب الإخراج: </span>
            <span class="fw-bold"> @if($outbound->manifest_type_number == 4)
                    إخراج نهائي
                @elseif($outbound->manifest_type_number == 3)
                    إعادة تصدير
                @elseif($outbound->manifest_type_number == 8)
                    تحويل جمركي
                @else
                    آخر
                @endif </span>
        </div>
    </div>

    <h4 class="my-2 fw-bold" style="color:#0c5cc3">التعهد:</h4>


    <div class="row">
        <div class="col-12">
            <div class="mt-1">
                نقر نحن شركة ............................................................ بأن البضاعة أعلاه قد تم إخراجها من مستودعات
                بوندد الشرقية تحت إشراف الجمارك ووفقاً للإجراءات القانونية، وتتحمل الشركة كامل المسؤولية المالية او
                القانونية وخلافه عن البضاعة المستلمه .
            </div>
        </div>
       <div class="col-4 mt-5">
           <div class="mb-2 fw-bold">توقيع ممثل الشركة:</div>
           <div class="mb-2"> الاسم: ________________________</div>
           <div class="mb-2"> المسمى الوظيفي: ________________</div>
           <div class="mb-2"> التوقيع: ______________</div>
           <div class="mb-2"> الختم: ______________</div>
       </div>

        <div class="col-4 mt-5">
            <div class="mb-2 fw-bold">توقيع موظف البوندد:</div>
            <div class="mb-2"> الاسم: ________________________</div>
            <div class="mb-2"> التوقيع: ______________</div>
        </div>

        <div class="col-4 mt-5">
            <div class="mb-2 fw-bold">توقيع موظف الجمارك (إن وجد)</div>
            <div class="mb-2"> الاسم: ________________________</div>
            <div class="mb-2"> التوقيع: ______________</div>
        </div>

    </div>
</div>
</body>
</html>
