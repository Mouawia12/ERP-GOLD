@extends('admin.layouts.master')
@section('content')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
    <!-- row opened -->
    <style>
        table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
            direction: rtl;
            text-align:center;
        }
        body{
            direction: rtl; 
        }
        table#account tr td {
            padding: 10px !important;
            font-size:14px !important;
        }
        table#account tr{
            border: 1px solid #eee;
        }
        table#account th{
            padding: 10px !important;
        }
         /* Style the caret/arrow */
         .caret {
            cursor: pointer;
            user-select: none; /* Prevent text selection */
        }

        /* Create the caret/arrow with a unicode, and style it */
        .caret::before {
            content: "\25B6";
            color: black;
            display: inline-block;
            margin-right: 6px;
        }

        /* Rotate the caret/arrow icon when clicked on (using JavaScript) */
        .caret-down::before {
            transform: rotate(90deg);
        }

    </style>
    <div class="row row-sm"> 
        <div class="col-xl-12">
            <div class="card"> 
                <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 " style="border:solid 1px gray">
                            <header>
                                    <div class="row" style="direction: ltr;">
                                        <div class="col-4 text-left">   
                                            <br> 
                                            <button type="button" class="btn btn-primary btnPrint" id="btnPrint"><i class="fa fa-print"></i></button>
                                        </div>
                                        <div class="col-4 c">
                                            <h4  class="alert alert-primary text-center">
                                               {{__('main.Balance_Sheet')}}
                                            </h4> 
                                            <h5 class="text-center">  {{$periodFrom}} - {{$periodTo}} </h5>
                                        </div>
                                        <div class="col-4 c">
                                       <span style="text-align: right;">
                                           {{''}}
                                        <br>  س.ت : {{''}}
                                        <br>  ر.ض :  {{''}}
                                        <br>  تليفون :   {{''}}
                                       </span>
                                        </div>
                                    </div>
                            </header> 
                        </div>
                    </div>                   
                <div class="card-body"> 
                    <div class="table-responsive hoverable-table">
                        <table class="display w-100 text-nowrap text-center" id="account"> 
                            <thead>  
                                <tr class="alert-info"> 
                                    <th>{{__('main.account_name')}}</th> 
                                    <th>{{__('main.total_debit')}}</th>
                                    <th>{{__('main.total_credit')}}</th>
                                    <th>{{__('main.balance')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ([$assetsAccount] ?? [] as $account)
                                    @include('admin.reports.balance_sheet.recursive', ['account' => $account])
                                @endforeach

                                @foreach ([$equityAccount] ?? [] as $account)
                                    @include('admin.reports.balance_sheet.recursive', ['account' => $account])
                                @endforeach

                                @foreach ([$liabilitiesAccount] ?? [] as $account)
                                    @include('admin.reports.balance_sheet.recursive', ['account' => $account])
                                @endforeach

                                <tr>
                                    <td class="text-right" style="font-size:20px !important">صافي الربح</td> 
                                    <td colspan="2"></td>
                                    <td>{{number_format(abs($profitTotal),2) }} {{ $profitTotal != 0 ? ' / ' . ($profitTotal > 0 ? __('main.credit') : __('main.debit')) : '' }}</td>
                                </tr>
                            </tbody>
                        </table>
                            </div>
                        </div>


                    </div>

                </div>


            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content --> 

    </div>
    <!-- End of Content Wrapper -->

</div>
 
@endsection 
@section('js') 
<script type="text/javascript">
    let id = 0; 
    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            printPage();
        }); 
    }); 

    function printPage() {
        var css = '@page { size: landscape; }',
            head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');
        style.type = 'text/css';
        style.media = 'print';

        if (style.styleSheet) {
            style.styleSheet.cssText = css;
        } else {
            style.appendChild(document.createTextNode(css));
        }
        head.appendChild(style);
        document.getElementById("main-header").style.display = 'none';
        document.getElementById("main-footer").style.display = 'none';
        //document.getElementById("card-header").style.display = 'none'; 
        window.print();
        document.getElementById("main-header").style.display = 'block';
        document.getElementById("main-footer").style.display = 'block';
        //document.getElementById("card-header").style.display = 'block'; 
    } 
 </script>
 
    
@endsection 

