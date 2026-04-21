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
        .print-report__header {
            direction: rtl;
            border: 1px solid #9aa6b2;
            border-radius: 4px;
            padding: 14px 18px;
        }
        .print-report__title {
            display: inline-block;
            min-width: 260px;
            margin: 0 auto 8px;
            padding: 10px 18px;
            background: #dfe7ff;
            color: #25375f;
            font-size: 16px;
            font-weight: 700;
        }
        .print-report__meta {
            color: #34405a;
            font-size: 13px;
            line-height: 1.7;
        }
        .print-report__table-wrap {
            overflow: visible;
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

        .caret-down::before {
            transform: rotate(90deg);
        }
        @media print {
            @page { size: A4 portrait; margin: 8mm; }
            html,
            body {
                width: 210mm;
                min-height: 297mm;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                direction: rtl;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body * {
                visibility: hidden;
            }
            .print-report,
            .print-report * {
                visibility: visible;
            }
            .print-report {
                position: absolute;
                inset: 0;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .print-report .row,
            .print-report .col-xl-12,
            .print-report .card,
            .print-report .card-body {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
            }
            .print-report .card {
                display: block !important;
            }
            .no-print,
            #btnPrint,
            .btnPrint {
                display: none !important;
            }
            .print-report__header {
                display: block !important;
                border: 1px solid #9aa6b2 !important;
                border-radius: 4px;
                padding: 8mm 10mm 6mm !important;
                margin: 0 0 7mm !important;
                page-break-inside: avoid;
            }
            .print-report__title {
                min-width: 230px;
                padding: 7px 16px !important;
                margin-bottom: 6px !important;
                font-size: 15px !important;
            }
            .print-report__meta {
                font-size: 11px !important;
                line-height: 1.7 !important;
            }
            .print-report__company {
                font-size: 10.5px !important;
                line-height: 1.7 !important;
            }
            .print-report__table-wrap,
            .table-responsive,
            .hoverable-table {
                width: 100% !important;
                overflow: visible !important;
            }
            table#account {
                width: 100% !important;
                min-width: 0 !important;
                border-collapse: collapse !important;
                table-layout: fixed;
                direction: rtl !important;
                font-size: 10.5px !important;
                color: #1f2937 !important;
                page-break-inside: auto;
            }
            table#account thead {
                display: table-header-group;
            }
            table#account tr {
                page-break-inside: avoid;
                break-inside: avoid;
                border: 0 !important;
            }
            table#account th,
            table#account td {
                border: 1px solid #d6deea !important;
                padding: 5px 7px !important;
                white-space: normal !important;
                vertical-align: middle !important;
            }
            table#account th {
                background: #dceff4 !important;
                color: #253047 !important;
                font-weight: 700 !important;
            }
            table#account th:first-child,
            table#account td:first-child {
                width: 42% !important;
                text-align: right !important;
            }
            table#account th:not(:first-child),
            table#account td:not(:first-child) {
                width: 19.333% !important;
                text-align: center !important;
            }
            table#account tbody tr:last-child td {
                background: #f4f7fb !important;
                font-weight: 700 !important;
            }
            .caret::before,
            .caret-down::before {
                display: none !important;
            }
        }
    </style>
    <div class="row row-sm print-report"> 
        <div class="col-xl-12">
            <div class="card"> 
                <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 print-report__header">
                            <header>
                                    <div class="row align-items-center">
                                        <div class="col-4 text-left">   
                                            <br> 
                                            <button type="button" class="btn btn-primary btnPrint no-print" id="btnPrint"><i class="fa fa-print"></i></button>
                                        </div>
                                        <div class="col-4 c text-center">
                                            <h4 class="text-center print-report__title">
                                               {{__('main.Balance_Sheet')}}
                                            </h4> 
                                            <div class="print-report__meta">
                                                <div>{{$periodFrom}} - {{$periodTo}}</div>
                                                <div>الفرع: {{ $branchLabel ?? ($branch?->name ?: 'جميع الفروع') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-4 c print-report__company">
                                       <span>
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
                    <div class="table-responsive hoverable-table print-report__table-wrap">
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
        window.print();
    }
 </script>
 
    
@endsection 
