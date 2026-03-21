<!DOCTYPE html>
<html>
<head>
    <title> طباعة الفروع </title>
    <meta charset="utf-8"/>
    <link href="{{asset('/assets/css/bootstrap.min.css')}}" rel="stylesheet"/>
    <style type="text/css" media="screen">
        @font-face {
            font-family: 'Almarai';
            src: url({{asset('fonts/Almarai.ttf')}});
        }

        span.badge {
            padding: 10px !important;
        }

        body, html {
            font-family: 'Almarai' !important;
        }

        .table-container {
            width: 70%;
            margin: 10px auto;
        }

        .no-print {
            position: fixed;
            bottom: 0;
            right: 10px;
            border-radius: 0;
            z-index: 9999;
        }
    </style>
    <style type="text/css" media="print">
        body, html {
            font-family: 'Almarai' !important;
        }

        span.badge {
            padding: 10px !important;
        }

        * {
            font-size: 14px !important;
            color: #000 !important;
            font-weight: bold !important;
        }

        .no-print, .noprint {
            display: none;
        }
    </style>
</head>
<body style="background: #fff">
<table class="table table-bordered table-container">
    <tbody>
    <tr>
        <td class="text-center">
            <img style="width: 100px!important; height: 100px!important;" src="{{asset('assets/img/logo.png')}}"
                 alt="">
            <h1 style="color: black !important;">
                @php
                    echo env('APP_NAME');
                @endphp
            </h1>
        </td>
    </tr>
    <tr>
        <td>
            <h4 class="text-center mt-2 mb-4">طباعة كل الفروع</h4>
            <table dir="rtl" class="table table-condensed display table-bordered text-center">
                <thead>
                <tr>
                    <th class="border-bottom-0 text-center">#</th>
                    <th class="border-bottom-0 text-center">اسم الفرع</th>
                    <th class="border-bottom-0 text-center"> التلفون</th>
                    <th class="border-bottom-0 text-center"> العنوان</th> 
                </tr>
                </thead>
                <tbody>
                @php
                    $i = 0;
                @endphp
                @foreach ($branches as $branch)
                    <tr>
                        <td>{{ ++$i }}</td>
                        <td>{{ $branch->branch_name}}</td>
                        <td>{{ $branch->branch_phone }}</td>
                        <td>{{ $branch->branch_address }}</td>
                        <td>{{ $branch->commercial_record }}</td>
                        <td>{{ $branch->license_number }}</td>
                        <td>{{ $branch->snap }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
<button onclick="window.print();" class="no-print btn btn-md btn-success text-white">اضغط للطباعة</button>
<script src="{{asset('assets/js/jquery.min.js')}}"></script>
</body>
</html>
