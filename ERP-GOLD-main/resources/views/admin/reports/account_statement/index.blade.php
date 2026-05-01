@extends('admin.layouts.master')
@section('content')
@can('employee.accounting_reports.show')
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
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            table { page-break-inside: auto; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
    @include('admin.reports.partials.accounting_print_styles', ['orientation' => 'landscape'])

    <div class="row row-sm accounting-print-report">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 accounting-print-header" id="head-right">
                            <header>
                                <div class="row">
                                    <div class="col-4 text-left">
                                        <button type="button" class="btn btn-primary btnPrint no-print accounting-print-button" id="btnPrint"><i class="fa fa-print"></i></button>
                                    </div>
                                    <div class="col-4 c">
                                        <h4 class="alert alert-primary text-center accounting-print-title">
                                             {{__('main.account_movement_report')}}
                                        </h4>
                                        <div class="accounting-print-meta text-center">
                                            <div>[ {{$periodFrom}} - {{$periodTo}} ]</div>
                                            <div><strong>{{$account->name}} </strong></div>
                                            <div>الفرع: {{ $branchSelection['branch_label'] ?? 'جميع الفروع' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-4 c accounting-print-company">
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
                        <div class="table-responsive hoverable-table accounting-print-table-wrap" style="direction: rtl;">
                                <table class="display w-100 text-nowrap table-bordered accounting-print-table accounting-wide-table" id="example1"
                                style="text-align: center;direction: rtl;">
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th class="text-center">{{__('main.date')}}</th>
                                        <th class="text-center">الوقت</th>
                                        <th class="text-center">الفرع</th>
                                        <th class="text-center">المستخدم</th>
                                        <th class="text-center">نوع المصدر</th>
                                        <th class="text-center">المرجع</th>
                                        <th class="text-center">{{__('main.document_type')}}</th>
                                        <th class="text-center">{{__('main.Debit')}}</th>
                                        <th class="text-center">{{__('main.Credit')}}</th>
                                        <th class="text-center">{{__('main.balance')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $runningBalance = $openingBalance['net'];
                                    @endphp
                                    @if($openingBalance['net'] != 0 || $openingBalance['debit'] != 0 || $openingBalance['credit'] != 0)
                                        <tr>
                                            <td class="text-center">1</td>
                                            <td class="text-center">--</td>
                                            <td class="text-center">--</td>
                                            <td class="text-center">--</td>
                                            <td class="text-center">--</td>
                                            <td class="text-center">--</td>
                                            <td class="text-center">--</td>
                                            <td class="text-center">رصيد اول المده</td>
                                            <td class="text-center">{{number_format($openingBalance['debit'],2)}}</td>
                                            <td class="text-center">{{number_format($openingBalance['credit'],2)}}</td>
                                            <td class="text-center">{{number_format(abs($runningBalance),2)}} {{ $runningBalance != 0 ? ' / ' . ($runningBalance > 0 ? __('main.debit') : __('main.credit')) : '' }}</td>
                                        </tr>
                                    @endif

                                    @foreach($documents??[] as $document)

                                    @php
                                        $currentBalance = $document['debit'] - $document['credit'];
                                        $balance = $runningBalance + $currentBalance;
                                        $runningBalance = $balance;
                                    @endphp
                                        <tr>
                                            <td class="text-center">{{$loop -> iteration}}</td>
                                            <td class="text-center"> {{\Carbon\Carbon::parse($document['date']) -> format('d-m-Y')}}</td>
                                            <td class="text-center">{{ $document['time'] ?? '-' }}</td>
                                            <td class="text-center">{{ $document['branch_name'] }}</td>
                                            <td class="text-center">{{ $document['user_name'] }}</td>
                                            <td class="text-center">{{ $document['source_type_label'] }}</td>
                                            <td class="text-center">{{ $document['reference_number'] }}</td>
                                            <td class="text-center"> {{$document['document_label']}}</td>
                                            <td class="text-center">{{number_format($document['debit'],2)}}</td>
                                            <td class="text-center">{{number_format($document['credit'],2)}}</td>
                                            <td class="text-center">{{number_format(abs($balance),2) }} {{ $balance != 0 ? ' / ' . ($balance > 0 ? __('main.debit') : __('main.credit')) : '' }}</td>
                                        </tr>

                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                        $totalBalance = $runningBalance;
                                    @endphp
                                    <tr style="background: #c3e6cb">
                                       <td class="text-center" colspan="7"></td>
                                        <td class="text-center">{{__('main.total_balance')}}</td>
                                        <td class="text-center" colspan="2"></td>
                                        <td class="text-center" >{{number_format(abs($totalBalance),2)}} {{ $totalBalance != 0 ? ' / ' . ($totalBalance > 0 ? __('main.debit') : __('main.credit')) : '' }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->

@endcan
@endsection
@section('js')
<script type="text/javascript">

    let id = 0;
    document.title = "{{__('main.account_movement_report') .'-'. $account->name}} ";

    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            printPage();
        });
    });

    function printPage() {
        window.print();
    }
    @if(request('auto_print') == '1')
    window.addEventListener('load', function () { window.print(); });
    @endif

</script>
@endsection

