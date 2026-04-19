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
        .caret {
            cursor: pointer;
            user-select: none;
        }
        .caret::before {
            content: "\25B6";
            color: black;
            display: inline-block;
            margin-right: 6px;
        }
        .caret-down::before {
            transform: rotate(90deg);
        }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            table { page-break-inside: auto; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            .caret-down::before { transform: rotate(90deg); }
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
                                               {{__('main.incoming_list')}}
                                            </h4> 
                                            <h5 class="text-center">  {{$periodFrom}} - {{$periodTo}} </h5>
                                            <h6 class="text-center">الفرع: {{ $branchLabel ?? ($branch?->name ?: 'جميع الفروع') }}</h6>
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

                                @foreach ([$revenuesAccount] ?? [] as $account)
                                    @include('admin.reports.income_statement.recursive', ['account' => $account])
                                @endforeach

                                @foreach([$expensesAccount]??[] as $account)
                                    @include('admin.reports.income_statement.recursive', ['account' => $account])
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
        window.print();
    }
 </script>
 
    
@endsection 
