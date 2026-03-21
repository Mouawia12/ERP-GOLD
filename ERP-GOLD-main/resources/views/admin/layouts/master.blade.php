<!DOCTYPE html>
<html  dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{{asset('assets/img/ficon.png')}}" type="image/png">
    <meta name="Keywords" content=""/>
    
    @include('admin.layouts.head')

    <style type="text/css" media="print">

        @media print {

            .app-content, .content {
                margin-right: 0 !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                -moz-print-color-adjust: exact;
                print-color-adjust: exact;
                -o-print-color-adjust: exact;
                direction: rtl; 
            }

            .no-print {
                display: none;
            }

            .printy {
                display: block !important;
            }
            table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
                direction: rtl;
                text-align:center;
            }
        }
    </style>
    <style>
        @font-face {
            font-family: 'Tajawal-Regular';
            src: url("{{asset('fonts/Tajawal-Regular.ttf')}}");
        }

        body, html,table {
            font-family: 'Tajawal-Regular' !important; 
            font-size: 14px; 
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Tajawal-Regular' !important;
        }

        .navigation.navigation-main {
            padding-bottom: 200px !important;
        }
        
        .dropdown-menu.dropdown-menu-right.show {
            width: 200px !important;
        }

        .btn-md, .badge {
            font-family: 'Tajawal-Regular' !important; 
        }

        .btn.dropdown-toggle.bs-placeholder, .btn.dropdown-toggle {
            height: 40px !important;
        }
        .select2-selection__rendered {
            line-height: 40px !important; border-radius: 0!important;
        }
        .select2-container .select2-selection--single {
            height: 40px !important;border-radius: 0!important;
        }
        .select2-selection__arrow {
            height: 40px !important;border-radius: 0!important;
        }
        .select2-search__field{
            height: 40px!important;
            line-height: 40px!important;
            outline: 0!important;
        }
        .dropdown-menu.show{
            right: 0!important;
            left: auto!important;
        }
        .side-menu__icon {
            font-size: 14px !important;
        }
        ::-webkit-scrollbar {
            width: 10px;
        }

        /* Track */
        ::-webkit-scrollbar-track {
            background: #fff!important;
            border-radius: 5px;
        }

        /* Handle */
        ::-webkit-scrollbar-thumb {
            background: #444!important;
            border-radius: 5px;
        }

        /* Handle on hover */
        ::-webkit-scrollbar-thumb:hover {
            background: #444!important;
        }
        div#main-footer {
            width: 100%;
            bottom: 0;
        }
        .btn-secondary {
            background-color: #0d6efd !important;
            border-color: #0d6efd; 
            border: 1px solid; 
        }
        .modal .form-control,.modal .select2-container,.modal select,.modal input, .modaltextarea{
            background:#f4f7fe !important;
            border:unset;
            font-weight: 700;
        } 
      
        label.modelTitle {
            font-weight: 700;
        }
        h4.alert.alert-primary.text-center {
            font-weight: 700;
        }
        .hoverable-table tbody .btn {
            margin-left: 2% !important;
            padding: 7px 16px !important;
        } 
        .btn-md, .badge { 
            font-size: 14px !important;
            font-weight: 700;
        }
  
        .dt-buttons.btn-group.flex-wrap {
            padding-right: 15% !important;
            padding-bottom:1% !important;
        }

        .global-loader { 
          top:50% !important;
          right:20%  !important;
          border: 16px solid #f3f3f3; /* Light grey */
          border-top: 16px solid #3498db; /* Blue */
          border-radius: 50%; 
          width: 50px !important;
          height: 50px !important;
          animation: spin 2s linear infinite;
        } 
    </style>
</head>

<body class="main-body app sidebar-mini">
@include('admin.layouts.main-sidebar')
<!-- main-content -->
<div class="main-content app-content">
@include('admin.layouts.main-header')
<!-- container -->
    <div class="container-fluid">
        @yield('page-header')
        @yield('content')
    </div>
</div>
@include('admin.layouts.footer')
@include('admin.layouts.footer-scripts')
</body>
</html>
