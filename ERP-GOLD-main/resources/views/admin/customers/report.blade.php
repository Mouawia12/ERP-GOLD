@extends('admin.layouts.master')

@section('content')
@php
    $partyLabel = $customer->type === 'customer' ? 'العميل' : 'المورد';
@endphp

<style>
    .customer-report-page {
        direction: rtl;
    }
    .customer-report-page .summary-card {
        border: 1px solid #e8e8e8;
        border-radius: 12px;
        padding: 16px;
        height: 100%;
        background: #fff;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
    }
    .customer-report-page .summary-card h5 {
        margin-bottom: 10px;
        color: #7a5b00;
    }
    .customer-report-page .report-table th,
    .customer-report-page .report-table td {
        vertical-align: middle;
        text-align: center;
    }
    .customer-report-page .carat-badge {
        display: inline-block;
        margin: 2px 0;
        padding: 4px 8px;
        border-radius: 999px;
        background: #f3f6fb;
        color: #2f4f6f;
        font-size: 13px;
    }
    .customer-report-page .filter-card {
        border-radius: 14px;
    }
</style>

<div class="container-fluid customer-report-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">كشف {{ $partyLabel }} التفصيلي</h3>
            <p class="mb-0 text-muted">
                {{ $customer->name }}
                @if($customer->phone)
                    <span class="mr-2">| {{ $customer->phone }}</span>
                @endif
                @if($customer->identity_number)
                    <span class="mr-2">| رقم الهوية: {{ $customer->identity_number }}</span>
                @endif
            </p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('customers', ['type' => $customer->type]) }}" class="btn btn-outline-secondary">
                رجوع إلى قائمة {{ $customer->type === 'customer' ? __('main.customers') : __('main.suppliers') }}
            </a>
        </div>
    </div>

    <div class="card filter-card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('customers.report', $customer->id) }}">
                <div class="row">
                    <div class="col-lg-2 col-md-3">
                        <label>من تاريخ</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label>إلى تاريخ</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label>من وقت</label>
                        <input type="time" name="from_time" class="form-control" value="{{ $filters['from_time'] ?? '' }}">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label>إلى وقت</label>
                        <input type="time" name="to_time" class="form-control" value="{{ $filters['to_time'] ?? '' }}">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label>رقم الفاتورة</label>
                        <input type="text" name="invoice_number" class="form-control" value="{{ $filters['invoice_number'] ?? '' }}" placeholder="مثال: SALE-1001">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label>الفرع</label>
                        <select name="branch_id" class="form-control">
                            <option value="">الكل</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 mt-3 mt-lg-0">
                        <label>المستخدم</label>
                        <select name="user_id" class="form-control">
                            <option value="">الكل</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? null) == $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 mt-3 mt-lg-0">
                        <label>العيار</label>
                        <select name="carat_id" class="form-control">
                            <option value="">الكل</option>
                            @foreach($carats as $carat)
                                <option value="{{ $carat->id }}" @selected(($filters['carat_id'] ?? null) == $carat->id)>
                                    {{ $carat->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 mt-3 mt-lg-0">
                        <label>نوع العملية</label>
                        <select name="operation_type" class="form-control">
                            <option value="">الكل</option>
                            <option value="sale" @selected(($filters['operation_type'] ?? null) === 'sale')>بيع</option>
                            <option value="sale_return" @selected(($filters['operation_type'] ?? null) === 'sale_return')>مرتجع بيع</option>
                            <option value="purchase" @selected(($filters['operation_type'] ?? null) === 'purchase')>شراء</option>
                            <option value="purchase_return" @selected(($filters['operation_type'] ?? null) === 'purchase_return')>مرتجع شراء</option>
                            <option value="receipt" @selected(($filters['operation_type'] ?? null) === 'receipt')>سند قبض</option>
                            <option value="payment" @selected(($filters['operation_type'] ?? null) === 'payment')>سند صرف</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">تطبيق الفلاتر</button>
                    <a href="{{ route('customers.report', $customer->id) }}" class="btn btn-outline-secondary">إعادة التعيين</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        @forelse($operationSummary as $summary)
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="summary-card">
                    <h5>{{ $summary['label'] }}</h5>
                    <div>عدد العمليات: <strong>{{ $summary['count'] }}</strong></div>
                    <div>إجمالي قبل الضريبة: <strong>{{ number_format($summary['line_total'], 2) }}</strong></div>
                    <div>إجمالي الضريبة: <strong>{{ number_format($summary['tax_total'], 2) }}</strong></div>
                    <div>الإجمالي النهائي: <strong>{{ number_format($summary['net_total'], 2) }}</strong></div>
                    <div>وزن داخل: <strong>{{ number_format($summary['in_weight'], 3) }}</strong></div>
                    <div>وزن خارج: <strong>{{ number_format($summary['out_weight'], 3) }}</strong></div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center mb-0">لا توجد عمليات ضمن الفلاتر الحالية.</div>
            </div>
        @endforelse
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h4 class="mb-0">العمليات التفصيلية</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered report-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>رقم الفاتورة</th>
                        <th>العملية</th>
                        <th>التاريخ</th>
                        <th>الوقت</th>
                        <th>الفرع</th>
                        <th>المستخدم</th>
                        <th>الدفع / الحسابات</th>
                        <th>تفصيل العيارات</th>
                        <th>وزن داخل</th>
                        <th>وزن خارج</th>
                        <th>قبل الضريبة</th>
                        <th>الضريبة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $transaction['bill_number'] }}</td>
                            <td>{{ $transaction['operation_label'] }}</td>
                            <td>{{ $transaction['date'] }}</td>
                            <td>{{ $transaction['time'] }}</td>
                            <td>{{ $transaction['branch_name'] }}</td>
                            <td>{{ $transaction['user_name'] }}</td>
                            <td>{{ $transaction['payment_type_label'] }}</td>
                            <td style="min-width: 220px;">
                                @forelse($transaction['carat_summary'] as $carat)
                                    <div class="carat-badge">
                                        {{ $carat['carat_title'] }}
                                        |
                                        داخل {{ number_format($carat['in_weight'], 3) }}
                                        |
                                        خارج {{ number_format($carat['out_weight'], 3) }}
                                    </div>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </td>
                            <td>{{ number_format($transaction['in_weight'], 3) }}</td>
                            <td>{{ number_format($transaction['out_weight'], 3) }}</td>
                            <td>{{ number_format($transaction['line_total'], 2) }}</td>
                            <td>{{ number_format($transaction['tax_total'], 2) }}</td>
                            <td>{{ number_format($transaction['net_total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center text-muted">لا توجد بيانات لعرضها.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">التجميع حسب العيار ونوع العملية</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered report-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
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
                    @forelse($caratSummary as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['operation_label'] }}</td>
                            <td>{{ $row['carat_title'] }}</td>
                            <td>{{ $row['invoice_count'] }}</td>
                            <td>{{ $row['line_count'] }}</td>
                            <td>{{ number_format($row['in_weight'], 3) }}</td>
                            <td>{{ number_format($row['out_weight'], 3) }}</td>
                            <td>{{ number_format($row['line_total'], 2) }}</td>
                            <td>{{ number_format($row['tax_total'], 2) }}</td>
                            <td>{{ number_format($row['net_total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">لا يوجد تجميع متاح ضمن الفلاتر الحالية.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
