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
    </style>
    @include('admin.reports.partials.accounting_print_styles', ['orientation' => 'portrait'])
    <div class="row row-sm print-report accounting-print-report">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 print-report__header accounting-print-header">
                            <header>
                                    <div class="row align-items-center">
                                        <div class="col-4 text-left">
                                            <br>
                                            <button type="button" class="btn btn-primary btnPrint no-print accounting-print-button" id="btnPrint"><i class="fa fa-print"></i></button>
                                        </div>
                                        <div class="col-4 c text-center">
                                            <h4 class="text-center print-report__title accounting-print-title">
                                               مراكز التكلفة — قائمة الدخل حسب الفروع
                                            </h4>
                                            <div class="print-report__meta accounting-print-meta">
                                                <div>{{$periodFrom}} - {{$periodTo}}</div>
                                                <div>الفروع: {{ $branchLabel ?? 'جميع الفروع' }}</div>
                                                @if(($accountLevel ?? null))<div>المستوى: حتى مستوى {{ $accountLevel }}</div>@endif
                                            </div>
                                        </div>
                                        <div class="col-4 c print-report__company accounting-print-company">
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
                    <div class="table-responsive hoverable-table print-report__table-wrap accounting-print-table-wrap">
                        <table class="display w-100 text-nowrap text-center accounting-print-table accounting-summary-table" id="account">
                            <thead>
                                <tr class="alert-info">
                                    <th>الحساب</th>
                                    @foreach ($report['branches'] as $branch)
                                        <th>{{ $branch->name }}</th>
                                    @endforeach
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $branchIds = $report['branches']->pluck('id')->all(); @endphp

                                @forelse ($report['sections'] as $section)
                                    @foreach ($section['rows'] as $row)
                                        <tr @if($row['level'] === 1) style="font-weight:700; background:#f4f7ff;" @endif>
                                            <td class="text-right" style="padding-right: {{ $row['level'] }}rem !important">
                                                {{ $row['name'] }}
                                            </td>
                                            @foreach ($branchIds as $branchId)
                                                <td>{{ number_format($row['values'][$branchId] ?? 0, 2) }}</td>
                                            @endforeach
                                            <td style="font-weight:600">{{ number_format($row['total'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="{{ count($report['branches']) + 2 }}">
                                            لا توجد حسابات إيرادات أو مصروفات في شجرة الحسابات.
                                        </td>
                                    </tr>
                                @endforelse

                                @if (! empty($report['sections']))
                                    <tr style="font-weight:700; background:#e8f5e9; font-size:15px">
                                        <td class="text-right">صافي الربح</td>
                                        @foreach ($branchIds as $branchId)
                                            <td>{{ number_format($report['net_by_branch'][$branchId] ?? 0, 2) }}</td>
                                        @endforeach
                                        <td>{{ number_format($report['grand_net'], 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
@endsection
