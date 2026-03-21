<!DOCTYPE html>
<html>
<head>
    <title> طباعة المستخدمين </title>
    <meta charset="utf-8"/>
    <link href="{{asset('/assets/css/bootstrap.min.css')}}" rel="stylesheet"/>
    <style type="text/css" media="screen">
        @font-face {
            font-family: 'Almarai';
            src: url({{asset('fonts/Almarai.ttf')}});
        }

        * {
            color: #000 !important;
            font-size: 14px !important;
            font-weight: bold !important;
        }

        .img-footer {
            width: 100% !important;
            height: 120px !important;
            max-height: 120px !important;

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

        * {
            font-size: 14px !important;
            color: #000 !important;
            font-weight: bold !important;
        }

        .img-footer {

            width: 100% !important;
            height: 120px !important;
            max-height: 120px !important;

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
            <img style="width: 100px!important; height: 100px!important;" src="{{asset('assets/img/logo.png')}}" alt="">
            <h1 style="color: black !important;">
                @php
                    echo env('APP_NAME');
                @endphp
            </h1>
        </td>
    </tr>
    <tr>
        <td>
            <h4 class="text-center mt-2 mb-4">طباعة كل المستخدمين</h4>
            <table dir="rtl" class="table table-condensed display table-bordered text-center">
                <thead>
                <tr>
                    <th class="border-bottom-0 text-center">#</th>
                    <th class="border-bottom-0 text-center">اسم المستخدم</th>
                    <th class="border-bottom-0 text-center">البريد الالكترونى</th>
                    <th class="border-bottom-0 text-center">الفرع</th>
                    <th class="border-bottom-0 text-center">الصلاحية</th>
					<th class="border-bottom-0 text-center">الصورة الشخصية</th>

                </tr>
                </thead>
                <tbody>
                @php
                    $i = 0;
                @endphp
                @foreach ($Admin as $admin)
                    <tr>
                        <td>{{ ++$i }}</td>
                        <td>{{ $admin ->name}}</td>
                        <td>{{ $admin ->email }}</td>
                        <td>
                            @if(empty($admin ->branch_id))
                                كل الفروع
                            @else
                                {{$admin ->branch->branch_name}}
                            @endif
                        </td>
                        <td>
                            {{$admin ->role_name}}
                        </td>
						 <td>
                            <img src="{{asset($admin ->profile_pic)}}"
                                 style="width: 70px;height: 70px;border-radius: 100%; padding: 3px; border: 1px solid #aaa;">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
<button onclick="window.print();" class="no-print btn btn-lg btn-success text-white">اضغط للطباعة</button>
<script src="{{asset('assets/js/jquery.min.js')}}"></script>
</body>
</html>
