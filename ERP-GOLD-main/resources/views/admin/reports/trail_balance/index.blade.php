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
                    <div class="card shadow mb-3 ">
                        <div class="card-header py-3 accounting-print-header" id="head-right">
                          <div class="row">
                            <div class="col-3 accounting-print-company">
                                {{''}}
                               <br>  س.ت : {{''}}
                               <br>  ر.ض :  {{''}}
                               <br>  تليفون :   {{''}}
                            </div>
                            <div class="col-6 title text-center">
                                <h4  class="alert alert-primary text-center accounting-print-title">
                                   {{__('main.balance_report')}}
                                </h4>
                                <div class="accounting-print-meta">
                                    <div>[ {{$periodFrom}} - {{$periodTo}} ]</div>
                                    <div>الفرع: {{ $branchLabel ?? ($branch?->name ?: 'جميع الفروع') }}</div>
                                    <div>المستوى: {{ $accountLevel ? 'مستوى ' . $accountLevel : 'تفصيلي (آخر مستوى)' }}</div>
                                </div>
                            </div>
                            <div class="col-3 text-left">
                                 <button type="button" class="btn btn-primary btnPrint no-print accounting-print-button" id="btnPrint"><i class="fa fa-print"></i></button>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive hoverable-table accounting-print-table-wrap" style="direction: rtl;">
                        @include('admin.reports.trail_balance.partials.table', [
                            'tableId' => 'example1',
                            'tableClass' => 'display w-100 table-bordered caption-top accounting-print-table accounting-wide-table',
                            'footerClass' => 'bg-primary text-white',
                        ])
                    </div>
                </div>
            </div>
        </div>
         <!-- End of Main Content -->
    </div>
    <!-- End of Page Wrapper -->

@endcan
@endsection
@section('js')
<script type="text/javascript">
    document.title = "{{__('main.balance_report')}}";
    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            window.print();
        });
    });
    @if(request('auto_print') == '1')
    window.addEventListener('load', function () { window.print(); });
    @endif
</script>
@endsection
