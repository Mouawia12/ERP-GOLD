@extends('admin.layouts.master')

@section('content')
<style>
    .daily-carat-report-page {
        direction: rtl;
    }
    .daily-carat-report-page .summary-card {
        border: 1px solid #e8e8e8;
        border-radius: 12px;
        padding: 16px;
        height: 100%;
        background: #fff;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
    }
    .daily-carat-report-page .report-table th,
    .daily-carat-report-page .report-table td {
        text-align: center;
        vertical-align: middle;
    }
</style>

<div class="container-fluid daily-carat-report-page">
    <div class="card shadow mb-4">
        <div class="card-header py-3 text-center">
            <h4 class="alert alert-primary text-center mb-3">التقرير اليومي للمبيعات والمشتريات حسب العيار</h4>
            <div>
                <strong>{{ $periodFrom }} - {{ $periodTo }}</strong>
                @if($branch)
                    <span class="mr-3">| الفرع: {{ $branch->name }}</span>
                @endif
                @if($user)
                    <span class="mr-3">| المستخدم: {{ $user->name }}</span>
                @endif
                @if($carat)
                    <span class="mr-3">| العيار: {{ $carat->title }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row mb-4">
        @forelse($operationSummary as $summary)
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="summary-card">
                    <h5>{{ $summary['operation_label'] }}</h5>
                    <div>عدد الأيام: <strong>{{ $summary['days_count'] }}</strong></div>
                    <div>عدد الفواتير: <strong>{{ $summary['invoice_count'] }}</strong></div>
                    <div>عدد البنود: <strong>{{ $summary['line_count'] }}</strong></div>
                    <div>وزن داخل: <strong>{{ number_format($summary['total_in_weight'], 3) }}</strong></div>
                    <div>وزن خارج: <strong>{{ number_format($summary['total_out_weight'], 3) }}</strong></div>
                    <div>قبل الضريبة: <strong>{{ number_format($summary['total_line_total'], 2) }}</strong></div>
                    <div>الضريبة: <strong>{{ number_format($summary['total_tax_total'], 2) }}</strong></div>
                    <div>الإجمالي: <strong>{{ number_format($summary['total_net_total'], 2) }}</strong></div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">لا توجد حركات ضمن الفلاتر الحالية.</div>
            </div>
        @endforelse
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h4 class="mb-0">التفصيل اليومي حسب العملية والعيار</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered report-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th>العملية</th>
                        <th>العيار</th>
                        <th>عدد الفواتير</th>
                        <th>عدد البنود</th>
                        <th>وزن داخل</th>
                        <th>وزن خارج</th>
                        <th>قبل الضريبة</th>
                        <th>الضريبة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->operation_date }}</td>
                            <td>{{ $row->operation_label }}</td>
                            <td>{{ $row->carat_title }}</td>
                            <td>{{ $row->invoice_count }}</td>
                            <td>{{ $row->line_count }}</td>
                            <td>{{ number_format($row->total_in_weight, 3) }}</td>
                            <td>{{ number_format($row->total_out_weight, 3) }}</td>
                            <td>{{ number_format($row->total_line_total, 2) }}</td>
                            <td>{{ number_format($row->total_tax_total, 2) }}</td>
                            <td>{{ number_format($row->total_net_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">لا توجد بيانات لعرضها.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">الإجماليات اليومية</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered report-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th>عدد الفواتير</th>
                        <th>عدد البنود</th>
                        <th>وزن داخل</th>
                        <th>وزن خارج</th>
                        <th>قبل الضريبة</th>
                        <th>الضريبة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyTotals as $total)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $total['operation_date'] }}</td>
                            <td>{{ $total['invoice_count'] }}</td>
                            <td>{{ $total['line_count'] }}</td>
                            <td>{{ number_format($total['total_in_weight'], 3) }}</td>
                            <td>{{ number_format($total['total_out_weight'], 3) }}</td>
                            <td>{{ number_format($total['total_line_total'], 2) }}</td>
                            <td>{{ number_format($total['total_tax_total'], 2) }}</td>
                            <td>{{ number_format($total['total_net_total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">لا يوجد تجميع يومي متاح.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
