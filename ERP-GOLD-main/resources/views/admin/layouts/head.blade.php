<!-- Title -->
<title> 
    @php
        echo env('APP_NAME');
    @endphp
</title>
<!-- Favicon -->
<link rel="icon" href="{{URL::asset('assets/img/logo.png')}}" type="image/x-icon"/>
<!-- Icons css -->
<link href="{{URL::asset('assets/css/icons.css')}}" rel="stylesheet">
<!--  Custom Scroll bar-->
<link href="{{URL::asset('assets/plugins/mscrollbar/jquery.mCustomScrollbar.css')}}" rel="stylesheet"/>
<!--  Sidebar css -->
<link href="{{URL::asset('assets/plugins/sidebar/sidebar.css')}}" rel="stylesheet">
<!-- Sidemenu css -->
<link rel="stylesheet" href="{{URL::asset('assets/css-rtl/sidemenu.css')}}">
<!-- datatables css -->
<link rel="stylesheet" href="{{URL::asset('assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">
<link rel="stylesheet" href="{{URL::asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}"> 
<link rel="stylesheet" href="{{URL::asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}"> 
<link href="{{asset('assets/plugins/select2/css/select2.min.css')}}"  rel="stylesheet" > 
@yield('css')
<!--- Style css -->
<link href="{{URL::asset('assets/css-rtl/style.css')}}" rel="stylesheet">
<!--- Dark-mode css -->
<link href="{{URL::asset('assets/css-rtl/style-dark.css')}}" rel="stylesheet">
<!---Skinmodes css-->
<link href="{{URL::asset('assets/css-rtl/skin-modes.css')}}" rel="stylesheet">
<link href="{{asset('assets/css-rtl/bootstrap-select.css')}}" rel="stylesheet"/>
<link href="{{asset('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}"  rel="stylesheet" >
<link href="{{asset('assets/css/datatables.min.css')}}" rel="stylesheet"/>

<link href="{{asset('css/all.min.css')}}" rel="stylesheet" />

<link rel="stylesheet" href="{{asset('assets/css-rtl/progress-chart.css')}}">
<meta name="csrf-token" content="{{ csrf_token() }}" />

