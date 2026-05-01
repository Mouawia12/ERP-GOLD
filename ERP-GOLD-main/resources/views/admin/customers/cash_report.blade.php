@extends('admin.layouts.master')

@section('content')
@php
    $partyLabel = $customer->type === 'customer' ? 'العميل' : 'المورد';
    $closingDirection = $closingNet > 0 ? __('main.debit') : ($closingNet < 0 ? __('main.credit') : '');
@endphp

<style>
    .customer-cash-report-page {
        direction: rtl;
    }
    .customer-cash-report-page .report-shell {
        border: 1px solid #e5ebf5;
        border-radius: 18px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(31, 61, 102, 0.08);
    }
    .customer-cash-report-page .report-head {
        padding: 22px 24px 18px;
        border-bottom: 1px solid #eef2f8;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }
    .customer-cash-report-page .report-title {
        margin: 0;
        font-size: 28px;
        font-weight: 800;
        color: #1f3454;
    }
    .customer-cash-report-page .report-subtitle {
        margin: 10px 0 0;
        color: #6c7a90;
        font-size: 14px;
    }
    .customer-cash-report-page .report-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-start;
    }
    .customer-cash-report-page .summary-card {
        border: 1px solid #e7edf6;
        border-radius: 14px;
        padding: 16px 18px;
        height: 100%;
        background: #fbfdff;
    }
    .customer-cash-report-page .summary-card .summary-label {
        color: #6d7f97;
        font-size: 13px;
        margin-bottom: 8px;
    }
    .customer-cash-report-page .summary-card .summary-value {
        font-size: 24px;
        font-weight: 800;
        color: #1b365d;
    }
    .customer-cash-report-page .summary-card .summary-meta {
        margin-top: 6px;
        color: #7b8798;
        font-size: 12px;
    }
    .customer-cash-report-page .filter-card {
        border: 1px solid #edf1f7;
        border-radius: 16px;
        background: #fff;
    }
    .customer-cash-report-page .filter-card label {
        font-weight: 700;
        color: #425770;
        margin-bottom: 6px;
    }
    .customer-cash-report-page .ledger-table th,
    .customer-cash-report-page .ledger-table td {
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }
    .customer-cash-report-page .ledger-table thead th {
        background: #f4f8fd;
        color: #32507a;
        border-bottom: 0;
    }
    @media print {
        #main-header,
        #main-footer,
        .main-sidebar {
            display: none !important;
        }
        .customer-cash-report-page .no-print {
            display: none !important;
        }
        .customer-cash-report-page .report-shell {
            box-shadow: none;
            border: 0;
        }
    }
</style>
@include('admin.reports.partials.result_print_styles')

<div class="container-fluid customer-cash-report-page erp-print-report">
    <div class="report-shell">
        <div class="report-head">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div class="mb-3 mb-md-0">
                    <h3 class="report-title">كشف {{ $partyLabel }} النقدي</h3>
                    <p class="report-subtitle">
                        {{ $customer->name }}
                        @if($customer->phone)
                            <span class="mr-2">| {{ $customer->phone }}</span>
                        @endif
                        @if($customer->identity_number)
                            <span class="mr-2">| رقم الهوية: {{ $customer->identity_number }}</span>
                        @endif
                        @if($accountName)
                            <span class="mr-2">| الحساب: {{ $accountName }}</span>
                        @endif
                    </p>
                </div>
                <div class="report-actions no-print">
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        data-print-open
                        data-print-url="{{ route('customers.report', $customer->id) }}"
                        data-print-target="_iframe"
                    >
                        التقرير التفصيلي
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.ErpPrint.printCurrentPage()">طباعة</button>
                    <a href="{{ route('customers', ['type' => $customer->type]) }}" class="btn btn-outline-dark">
                        رجوع إلى قائمة {{ $customer->type === 'customer' ? __('main.customers') : __('main.suppliers') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="p-4">
            @if(! $accountName)
                <div class="alert alert-warning mb-4">
                    لا يوجد حساب محاسبي مرتبط بهذا {{ $partyLabel }}، لذلك لا يمكن عرض التقرير النقدي له حاليًا.
                </div>
            @endif

            <div class="card filter-card shadow-sm mb-4 no-print">
                <div class="card-body">
                    <form method="GET" action="{{ route('customers.report.cash', $customer->id) }}">
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
                                <label>المرجع / رقم الفاتورة</label>
                                <input type="text" name="invoice_number" class="form-control" value="{{ $filters['invoice_number'] ?? '' }}" placeholder="مثال: INV-1001">
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
                                <label>نوع المصدر</label>
                                <select name="source_type" class="form-control">
                                    <option value="">الكل</option>
                                    <option value="invoice" @selected(($filters['source_type'] ?? null) === 'invoice')>فاتورة</option>
                                    <option value="voucher" @selected(($filters['source_type'] ?? null) === 'voucher')>سند مالي</option>
                                    <option value="manual" @selected(($filters['source_type'] ?? null) === 'manual')>قيد يدوي</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 d-flex flex-wrap" style="gap: 10px;">
                            <button type="submit" class="btn btn-primary">تطبيق الفلاتر</button>
                            <a href="{{ route('customers.report.cash', $customer->id) }}" class="btn btn-outline-secondary">إعادة التعيين</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-3 col-sm-6 mb-3">
                    <div class="summary-card">
                        <div class="summary-label">رصيد أول المدة</div>
                        <div class="summary-value">{{ number_format(abs($openingBalance['net']), 2) }}</div>
                        <div class="summary-meta">
                            {{ $openingBalance['net'] != 0 ? ($openingBalance['net'] > 0 ? __('main.debit') : __('main.credit')) : 'متوازن' }}
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 mb-3">
                    <div class="summary-card">
                        <div class="summary-label">إجمالي المدين للفترة</div>
                        <div class="summary-value">{{ number_format($periodTotals['debit'], 2) }}</div>
                        <div class="summary-meta">الحركات المدينة داخل الفلاتر الحالية</div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 mb-3">
                    <div class="summary-card">
                        <div class="summary-label">إجمالي الدائن للفترة</div>
                        <div class="summary-value">{{ number_format($periodTotals['credit'], 2) }}</div>
                        <div class="summary-meta">الحركات الدائنة داخل الفلاتر الحالية</div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 mb-3">
                    <div class="summary-card">
                        <div class="summary-label">الرصيد الختامي</div>
                        <div class="summary-value">{{ number_format(abs($closingNet), 2) }}</div>
                        <div class="summary-meta">{{ $closingDirection !== '' ? $closingDirection : 'متوازن' }}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered ledger-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>الوقت</th>
                            <th>الفرع</th>
                            <th>المستخدم</th>
                            <th>نوع المصدر</th>
                            <th>المرجع</th>
                            <th>البيان</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>الرصيد الجاري</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBalance = $openingBalance['net'];
                            $openingRowIndex = 1;
                        @endphp
                        @if($openingBalance['net'] != 0 || $openingBalance['debit'] != 0 || $openingBalance['credit'] != 0)
                            <tr>
                                <td>{{ $openingRowIndex }}</td>
                                <td>--</td>
                                <td>--</td>
                                <td>--</td>
                                <td>--</td>
                                <td>--</td>
                                <td>--</td>
                                <td>رصيد أول المدة</td>
                                <td>{{ number_format($openingBalance['debit'], 2) }}</td>
                                <td>{{ number_format($openingBalance['credit'], 2) }}</td>
                                <td>
                                    {{ number_format(abs($runningBalance), 2) }}
                                    {{ $runningBalance != 0 ? ' / ' . ($runningBalance > 0 ? __('main.debit') : __('main.credit')) : '' }}
                                </td>
                            </tr>
                        @endif

                        @forelse($documents as $document)
                            @php
                                $runningBalance += $document['debit'] - $document['credit'];
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration + ($openingBalance['net'] != 0 || $openingBalance['debit'] != 0 || $openingBalance['credit'] != 0 ? 1 : 0) }}</td>
                                <td>{{ \Carbon\Carbon::parse($document['date'])->format('d-m-Y') }}</td>
                                <td>{{ $document['time'] }}</td>
                                <td>{{ $document['branch_name'] }}</td>
                                <td>{{ $document['user_name'] }}</td>
                                <td>{{ $document['source_type_label'] }}</td>
                                <td>{{ $document['reference_number'] }}</td>
                                <td>{{ $document['document_label'] }}</td>
                                <td>{{ number_format($document['debit'], 2) }}</td>
                                <td>{{ number_format($document['credit'], 2) }}</td>
                                <td>
                                    {{ number_format(abs($runningBalance), 2) }}
                                    {{ $runningBalance != 0 ? ' / ' . ($runningBalance > 0 ? __('main.debit') : __('main.credit')) : '' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">لا توجد حركات نقدية لعرضها ضمن الفلاتر الحالية.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr style="background: #eff6ff; font-weight: 700;">
                            <td colspan="8">الإجمالي</td>
                            <td>{{ number_format($periodTotals['debit'], 2) }}</td>
                            <td>{{ number_format($periodTotals['credit'], 2) }}</td>
                            <td>
                                {{ number_format(abs($closingNet), 2) }}
                                {{ $closingDirection !== '' ? ' / ' . $closingDirection : '' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
