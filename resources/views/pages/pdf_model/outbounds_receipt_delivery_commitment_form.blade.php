<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ 'نموذج استلام بيان '.$outbound->outbound_number  }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            font-size: 18px;
        }
        .print-area {
            border: 1px solid #000;
            padding: 30px;
        }
        .line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            width: 100%;
            height: 25px;
        }
        .signature-box {
            margin-top: 50px;
        }
        @media print {
            .no-print {
                display: none;
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
            <img src="{{asset('assets/media/logos/default-dark-new.png')}}" alt="Right Logo" class="img-fluid"
                 style="max-width: 180px; max-height: 75px;">
        </div>
    </div>

    <div class="text-center mb-5 mt-2">
        <h3>مركز جمرك عمان / قسم المستودعات العامة</h3>
        <h4>بوندد الشرقية - رقم البوندد (618)</h4>
        <h5 class="fw-bold">نموذج استلام بيان</h5>
    </div>
    <p>
        أقر أنا الموقع أدناه …………………………………… وأحمل الرقم الوطني  ……………… وتفويضًا عن   <span class="fw-bold">{{$outbound->EnterRequest?->company?->name}}</span>، أني قد استلمت البيان رقم
        <span dir="ltr" class="fw-bold">{{$outbound->outbound_number}}</span>.
    </p>

    <p>
        وأتعهد بالمحافظة عليها وتسليمها إلى الجهات المعنية دون أدنى مسؤولية على شركة الفاروقي للخدمات اللوجستية والتخزين و/أو بوندد الشرقية.
    </p>

    <div class="signature-box row mt-5">
        <div class="col-md-3 col-sm-3">
            <p>الاسم: <span class="line"></span></p>
        </div>
        <p>
        <div class="col-sm-3">
            <p>الرقم الوطني: <span class="line"></span></p>
        </div>
        <p>
        <div class="col-sm-3">
            <p> رقم الهاتف: <span class="line"></span></p>
        </div>
        <p>
        <div class="col-sm-3">
            <p>التوقيع: <span class="line"></span></p>
        </div>

    </div>

</div>
</body>
<script>
    window.print()
</script>
</html>
